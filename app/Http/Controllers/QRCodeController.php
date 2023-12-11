<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Mpdf\QrCode\QrCode;
use Mpdf\QrCode\Output;
use Exception;

class QRCodeController extends Controller
{
    private $struct;
    public $pixcopiaecola;

    public function __construct(){
        $this->struct = [
            [
                'id' => '00',
                'lc' => '02',
                'content' => '01'
            ],
            [
                'id' => '01',
                'lc' => '02',
                'content' => '12'
            ],
            [
                'id' => '26',
                'lc' => '',
                'content' => ''
            ],
            [
                'id' => '00',
                'lc' => '14',
                'content' => 'br.gov.bcb.pix'
            ],
            [
                'id' => '25',
                'lc' => '',
                'content' => ''
            ],
            [
                'id' => '52',
                'lc' => '04',
                'content' => '0000'
            ],
            [
                'id' => '53',
                'lc' => '03',
                'content' => '986'
            ],
            [
                'id' => '54',
                'lc' => '',
                'content' => ''
            ],
            [
                'id' => '58',
                'lc' => '02',
                'content' => 'BR'
            ],
            [
                'id' => '59',
                'lc' => '',
                'content' => ''
            ],
            [
                'id' => '60',
                'lc' => '',
                'content' => ''
            ],
            [
                'id' => '62',
                'lc' => '',
                'content' => ''
            ],
            [
                'id' => '05',
                'lc' => '03',
                'content' => '***'
            ],
            [
                'id' => '63',
                'lc' => '04',
                'content' => ''
            ]
        ];
    }

    public function transform(Request $request){
        try{
            $emv = $request->query('emv');

            $this->struct[2]['lc'] = (string) 22 + strlen($emv);

            $this->struct[4]['lc'] = strlen($emv) < 10? '0' .  (string) strlen($emv) : (string) strlen($emv) ;
            $this->struct[4]['content'] = $emv;

            $this->struct[7]['content'] = '100.00';
            $this->struct[7]['lc'] = strlen($this->struct[7]['content']) < 10? '0' . (string) strlen($this->struct[7]['content']) : (string) strlen($this->struct[7]['content']);

            $this->struct[9]['content'] = 'EMPRESA';
            $this->struct[9]['lc'] = strlen($this->struct[9]['content']) < 10? '0' . (string) strlen($this->struct[9]['content']) : (string) strlen($this->struct[9]['content']);

            $this->struct[10]['content'] = 'SAO PAULO';
            $this->struct[10]['lc'] = strlen($this->struct[10]['content']) < 10? '0' . (string) strlen($this->struct[10]['content']) : (string) strlen($this->struct[10]['content']);

            $this->struct[11]['lc'] = strlen($this->struct[11]['content']) < 3? '0' . (string) 7 + strlen($this->struct[11]['content']) : (string) 7 + strlen($this->struct[11]['content']);


            foreach($this->struct as $field){
                $this->pixcopiaecola .= $field['id'] . $field['lc'] . $field['content'];
            }

            $crc = $this->crc($this->pixcopiaecola);
            $this->struct[13]['content'] = $crc;
            $this->pixcopiaecola .= $crc;
        }
        catch(Exception $e){
            Log::debug($e->getMessage());
        }

        $qrCode = new QrCode($this->pixcopiaecola);
        $image = (new Output\Png)->output($qrCode,400);

        $filePath = base_path() . '/storage/qrcode.png';
        file_put_contents($filePath, $image);

        return ['pix-copia-e-cola' => $this->pixcopiaecola, 'qr-code' => $filePath];
    }

    private function crc($str) {
        $crc = 0xFFFF;
        
        for($c = 0; $c < strlen($str); $c++) {
            $crc ^= ord(substr($str, $c, 1)) << 8;
            for($i = 0; $i < 8; $i++) {
                 if($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                 } else {
                    $crc = $crc << 1;
                 }
           }
        }
        $hex = $crc & 0xFFFF;
        $hex = dechex($hex);
        $hex = strtoupper($hex);
        $hex = str_pad($hex, 4, '0', STR_PAD_LEFT);
     
        return $hex;
     }
}
