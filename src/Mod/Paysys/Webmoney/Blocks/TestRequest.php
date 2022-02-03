<?php
namespace Mod\Paysys_Webmoney\Blocks;
class TestRequest extends \Verba\Block\Html{

  public $content = '';

  function build(){

    $this->content = '<form action="/order/notify/webmoney" method="POST" id="rqForm">
<table>';
    $fields = array (
      'LMI_PREREQUEST' => '0',
      'LMI_MODE' => '1',
      'LMI_PAYMENT_AMOUNT' => '1.20',
      'LMI_PAYEE_PURSE' => 'R872857781921',
      'LMI_PAYER_WM' => '481370726642',
      'LMI_PAYER_PURSE' => 'R707141292300',
      'LMI_PAYER_COUNTRYID' => 'UA',
      'LMI_PAYER_PCOUNTRYID' => 'UA',
      'LMI_PAYER_IP' => '127.0.0.1',
      'LMI_PAYMENT_DESC' => 'Счет #B82387345',
      'LMI_LANG' => 'ru-RU',
      'LMI_DBLCHK' => 'SMS',
      'LMI_HASH2' => 'D054C5A3ADBFBDEC702469B55FC3A11A1828B629AAEFF2451707CAC0A13D956F',
      'orderCode' => 'B82387345',
    );

    foreach($fields as $fCode => $fValue){
      $this->content .= "\n".'<tr><td>'.$fCode.'</td> <td><input type="text" name="'.$fCode.'" value="'.addslashes($fValue).'"></td></tr>';
    }

    $this->content .= '</table>';
    $this->content .= '<button type="submit">-></button>';
    $this->content .= '</form>';

    return $this->content;
  }
}
