 <?php
#******************************************************************************
#* Name          : dpspxsample.php
#* Description   : Direct Payment Solutions Payment Express PHP Sample
#* Copyright (c) : Direct Payment Solutions 2001
#* Date          : 2001-05-03
#* References    : http://www.paymentexpress.co.nz/px/pxxml.asp
#* Modifications : NONE
#******************************************************************************
 
# This file is a SAMPLE showing usage of Payment Express XML messages from PHP.
# See the full documentation of the Payment Express XML message format at
 
# MifMessage.
# Use this class to parse a DPS PX MifMessage in XML form,
# and access the content.
class MifMessage
{
  var $xml_;
  var $xml_index_;
  var $xml_value_;
 
  # Constructor:
  # Create a MifMessage with the specified XML text.
  # The constructor returns a null object if there is a parsing error.
  function MifMessage($xml)
  {
    $p = xml_parser_create();
    xml_parser_set_option($p,XML_OPTION_CASE_FOLDING,0);
    $ok = xml_parse_into_struct($p, $xml, &$value, &$index);
    xml_parser_free($p);
    if ($ok)
    {
      $this->xml_ = $xml;
      $this->xml_value_ = $value;
      $this->xml_index_ = $index;
    }
  // print_r($this->xml_value_); # JH_DEBUG
  }
 
  # Return the value of the specified top-level attribute.
  # This method can only return attributes of the root element.
  # If the attribute is not found, return "".
  function get_attribute($attribute)
  {
    #$attribute = strtoupper($attribute);
    $attributes = $this->xml_value_[0]["attributes"];
    return $attributes[$attribute];
  }
 
  # Return the text of the specified element.
  # The element is given as a simplified XPath-like name.
  # For example, "Link/ServerOk" refers to the ServerOk element
  # nested in the Link element (nested in the root element).
  # If the element is not found, return "".
  function get_element_text($element)
  {
   // print_r($this->xml_value_); # JH_DEBUG
    $index = $this->get_element_index($element, 0);
    if ($index == 0)
    {
      return "";
    }
    else
    {
      return $this->xml_value_[$index]["value"];
    }
  }
 
  # (internal method)
  # Return the index of the specified element,
  # relative to some given root element index.
  #
  function get_element_index($element, $rootindex = 0)
  {
    #$element = strtoupper($element);
    $pos = strpos($element, "/");
    if ($pos !== false)
    {
      # element contains '/': find first part
      $start_path = substr($element,0,$pos);
      $remain_path = substr($element,$pos+1);
      $index = $this->get_element_index($start_path, $rootindex);
      if ($index == 0)
      {
        # couldn't find first part; give up.
        return 0;
      }
      # recursively find rest
      return $this->get_element_index($remain_path, $index);
    }
    else
    {
      # search from the parent across all its children
      # i.e. until we get the parent's close tag.
      $level = $this->xml_value_[$rootindex]["level"];
      if ($this->xml_value_[$rootindex]["type"] == "complete")
      {
        return 0;   # no children
      }
      $index = $rootindex+1;
      while ($index<count($this->xml_value_) && 
             !($this->xml_value_[$index]["level"]==$level && 
               $this->xml_value_[$index]["type"]=="close"))
      {
        # if one below parent and tag matches, bingo
        if ($this->xml_value_[$index]["level"] == $level+1 &&
#            $this->xml_value_[$index]["type"] == "complete" &&
            $this->xml_value_[$index]["tag"] == $element)
        {
          return $index;
        }
        $index++;
      }
      return 0;
    }
  }
}
 
#
# This function displays the purchase form.
#
function show_form($name="",$amount="",$ccnum="",$ccyy="",$ccmm="")
{
?>
<h1>This is a sample only.</h1>
<hr>
<h1>Order Confirmation</h1>
<p>
You have ordered the following products:
<table>
  <tr>
    <td>Microsoft Windows 911</td>
    <td> </td>
    <td>$1000.00</td>
  </tr>
  <tr>
    <td>PHP</td>
    <td> </td>
    <td>(free)</td>
  </tr>
</table>
<p>
However, since this is just a sample,
we'll let you put whatever payment amount you'd like.
<form method="POST">
  <table>
      <tr>
        <td>Credit Card Name</td>
        <td><input type="text" name="name" value="<?echo $name?>"></td>
      </tr>
    <tr>
      <td>Amount</td>
      <td><input type="text" name="amount" value="<?echo $amount?>"></td>
    </tr>
    <tr>
      <td>Credit Card Number</td>
      <td><input type="text" maxlength="16" name="ccnum" value="<?echo $ccnum?>"></td>
    </tr>
    <tr>
      <td>Credit Card Expiry Date</td>
      <td><select name="ccyy" size="1">
             <?
             for ($y=0; $y<6; $y++)
             {
               $yy = sprintf("%02d", $y); 
               $yyyy = "20" . $yy;
               echo "<option value=\"$yy\"";
               if ($yy==$ccyy)
               {
                 echo " selected";
               }
               echo ">$yyyy\n";
             }
             ?>
          </select>
          <select name="ccmm" size="1">
            <?
            for ($m=1; $m<13; $m++)
            {
              $mm = sprintf("%02d", $m);
              echo "<option value=\"$mm\"";
              if ($mm == $ccmm)
              {
                echo " selected";
              }
              echo ">$mm\n";
            }
            ?>
          </select>
      </td>
    </tr>
  </table>
  <br>
  <input type="submit" value="Submit">
  <input type="reset"  value="Reset">
  <input type="button" value="Cancel" onclick="history.go(-1);">
</form>
<?
}
 
 
#
# This function reads lines from the socket until a complete message occurs.
# (This method is a bit flakey: 
# it _will_ break if there are not line breaks between messages 
# on the input stream.)
#
function get_mifmessage($socket)
{
  unset($msg);
$buffer=fread($socket,2048);
/*
  while (!$msg && !feof($socket))
  {
print "going in";
    $buffer .= fgets($socket,1024);
    $msg = new MifMessage($buffer);
  }
*/
//print $buffer;
$msg = new MifMessage($buffer);
  //echo "buffer = [$buffer]\n"; # JH_DEBUG
  return $msg;
}
 
#
# This function is a simple example of
# how to send and receive Payment Express XML
# messages to process a transaction.
#
function process_request($name,$amount,$ccnum,$ccyy,$ccmm)
{
  #
  # Part 1: use doLinkConnect message to check link, and get a TxnRef.
  #
$cmdDoLinkConnect="<MifCommand action=\"doLinkStatus\">";
$cmdDoLinkConnect.="<Link>";
$cmdDoLinkConnect.="</Link>";
$cmdDoLinkConnect.="</MifCommand>"; 
$socket = fsockopen("127.0.0.1",3004); //could try 127.0.0.1
  if (!$socket)
  {
    echo "socket connect failure\n";
    return;
  }
  fputs($socket,$cmdDoLinkConnect);
  $msg = get_mifmessage($socket);
if (!$msg)
  {
    echo "Error: problem receiving message from PX Server.\n";
    fclose($socket);
    return;
  }
/*
  if ($msg->get_element_text("Link/ServerOk") != "1")
  {
    echo "Server NOT OK.\n";
    fclose($socket);
    return;
  }
 
  if ($msg->get_element_text("Link/LinkOk") != "1")
  {
    echo "Link NOT OK.\n";
    fclose($socket);
    return;
  }
*/
 
$txnref=rand(1,100000);
  //$txnref = uniqid();  #You need to generate you own unqiue reference.
  $cmdDoTxnTransaction = "<MifCommand action=\"doTxnTransaction\">";
  $cmdDoTxnTransaction .= "<TxnRef>$txnref</TxnRef>";
  $cmdDoTxnTransaction .= "<Txn>";
  $cmdDoTxnTransaction .= "<Amount>$amount</Amount>";
  $cmdDoTxnTransaction .= "<CardHolderName>$name</CardHolderName>";
  $cmdDoTxnTransaction .= "<CardNumber>$ccnum</CardNumber>";
  $cmdDoTxnTransaction .= "<DateExpiry>$ccmm$ccyy</DateExpiry>";
  #$cmdDoTxnTransaction .= "<GroupAccount>9997</GroupAccount>";
  $cmdDoTxnTransaction .= "<Username>Insert DPS Username Here</Username>";
  $cmdDoTxnTransaction .= "<Password>Insert DPS Password Here</Password>";
  $cmdDoTxnTransaction .= "<TxnType>Purchase</TxnType>";
  $cmdDoTxnTransaction .= "</Txn>";
  $cmdDoTxnTransaction .= "</MifCommand>";
 
  fputs($socket, $cmdDoTxnTransaction);
 
  $msg = get_mifmessage($socket);
  $success        = $msg->get_element_text("Txn/Success");
  $reco           = $msg->get_element_text("Txn/ReCo");
  $responsetext   = $msg->get_element_text("Txn/ResponseText");
 
  $helptext       = $msg->get_element_text("Txn/HelpText");
  $datesettlement = $msg->get_element_text("Txn/Transaction/DateSettlement");
 
  if ($success == "1")
  {
    echo "Transaction Success<br>";
    echo "DateSettlement = [$datesettlement]<br>";
  }
  else
  {
    echo "Transaction Failure<br>";
    echo "ReCo = [$reco]<br>";
    echo "ResponseText = [$responsetext]<br>";
    echo "HelpText = [$helptext]<br>";
  }
  fclose($socket);
}
 
?>
 
<head>
<title>DPS PX Sample -- PHP</title>
</head>
 
<?php
    $name = $_REQUEST["name"];
    $amount = $_REQUEST["amount"];
    $ccnum = $_REQUEST["ccnum"];
    $ccyy = $_REQUEST["ccyy"];
    $ccmm = $_REQUEST["ccmm"];
  if (!isset($name) && !isset($amount) && !isset($ccnum) && !isset($ccyy) && !isset($ccmm))
  {
    show_form();
    #process_request("JOhn","10.00","4111111111111111","06","06");
  }
  elseif (empty($name) || empty($amount) || empty($ccnum) || empty($ccyy) || empty($ccmm))
  {
    show_form($name,$amount,$ccnum,$ccyy,$ccmm);
    echo "Please fill in all fields.\n";
  }
  else
  {
    process_request($name,$amount,$ccnum,$ccyy,$ccmm);
  }
?>

      
              
