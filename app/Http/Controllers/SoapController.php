<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;

class SoapController extends Controller
{
    public function getUser()
    {
        $IP = "192.168.1.10"; // Set the IP address
        $Key = "0"; // Set the key
        if (empty($IP)) $IP = "192.168.1.10";
        if (empty($Key)) $Key = "0";

        $soap_request = "<GetAttLog>
                            <ArgComKey xsi:type=\"xsd:integer\">" . $Key . "</ArgComKey>
                            <Arg><PIN xsi:type=\"xsd:integer\">All</PIN></Arg>
                        </GetAttLog>";

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: text/xml\r\n",
                'content' => $soap_request,
            ],
        ]);

        $url = "http://" . $IP . "/iWsService"; // Construct the URL

        $response = file_get_contents($url, false, $context);

        if ($response) {
            $buffer = Parse_Data($response, "<GetAttLogResponse>", "</GetAttLogResponse");
            $buffer = explode("\r\n", $buffer);
            foreach ($buffer as $line) {
                if($line != "")
                {
                    $data = Parse_Data($line, "<Row>", "</Row>");
                    $pin = Parse_Data($data, "<PIN>", "</PIN>");
                    $datetime = Parse_Data($data, "<DateTime>", "</DateTime>");
                    $status = Parse_Data($data, "<Status>", "</Status");
                    $date = explode(" ",$datetime)[0];
                    $time = explode(" ",$datetime)[1];
                    Attendance::create([
                        'pin' => $pin,
                        'date' => $date,
                        'time' => $time,
                        'status' => $status
                    ]);
                }
                

                // You can insert the data into your database here
                // For example, using Laravel's Eloquent:
                // YourModel::create(['pin' => $pin, 'datetime' => $datetime, 'status' => $status]);
                
                
            }
        } else {
            echo "Koneksi Gagal";
        }
        // dd(Attendance::select('date')->distinct()->get());
        return view('soap-response',  [
            'data' => Attendance::paginate(10)
        ]);
    }
}

function Parse_Data($data, $p1, $p2)
{
    $data = " " . $data;
    $hasil = "";
    $awal = strpos($data, $p1);
    if ($awal !== false) {
        $akhir = strpos(strstr($data, $p1), $p2);
        if ($akhir !== false) {
            $hasil = substr($data, $awal + strlen($p1), $akhir - strlen($p1));
        }
    }
    return $hasil;
}
