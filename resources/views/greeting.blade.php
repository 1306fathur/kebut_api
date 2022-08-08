<html>
<style>
html,body{
	background-color:#111;
	color:#fff;
}
.container {
  height: 100vh;
  font-family: Helvetica;
}

.loader {
  height: 20px;
  width: 250px;
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  margin: auto;
}
p{
  font-weight:400;  
  top: 0;
  bottom: 0;  
  right: 0;
  margin: auto;
}
.loader--dot {
  animation-name: loader;
  animation-timing-function: ease-in-out;
  animation-duration: 3s;
  animation-iteration-count: infinite;
  height: 20px;
  width: 20px;
  border-radius: 100%;
  background-color: black;
  position: absolute;
  border: 2px solid white;
}
.loader--dot:first-child {
  background-color: #8cc759;
  animation-delay: 0.5s;
}
.loader--dot:nth-child(2) {
  background-color: #8c6daf;
  animation-delay: 0.4s;
}
.loader--dot:nth-child(3) {
  background-color: #ef5d74;
  animation-delay: 0.3s;
}
.loader--dot:nth-child(4) {
  background-color: #f9a74b;
  animation-delay: 0.2s;
}
.loader--dot:nth-child(5) {
  background-color: #60beeb;
  animation-delay: 0.1s;
}
.loader--dot:nth-child(6) {
  background-color: #fbef5a;
  animation-delay: 0s;
}
.loader--text {
  position: absolute;
  top: 200%;
  left: 0;
  right: 0;
  width: 4rem;
  margin: auto;
}
.loader--text:after {
  content: "Loading";
  font-weight: bold;
  animation-name: loading-text;
  animation-duration: 3s;
  animation-iteration-count: infinite;
}

@keyframes loader {
  15% {
    transform: translateX(0);
  }
  45% {
    transform: translateX(230px);
  }
  65% {
    transform: translateX(230px);
  }
  95% {
    transform: translateX(0);
  }
}
@keyframes loading-text {
  0% {
    content: "Loading";
  }
  25% {
    content: "Loading.";
  }
  50% {
    content: "Loading..";
  }
  75% {
    content: "Loading...";
  }
}

</style>

    <body>
	
        <p><b>Hello, <?php echo $userName; ?></b><br/>
		<?php 

// $words = $ttl_price . '' . $MALLID . 'NRd509eQng1F' . '' . $id_transaksi;

if($id_transaksi > 0){
?>
		Tunggu sebentar, anda akan diarahkan ke halaman pembayaran ...</p>
		<div class='container'>
  <div class='loader'>
    <div class='loader--dot'></div>
    <div class='loader--dot'></div>
    <div class='loader--dot'></div>
    <div class='loader--dot'></div>
    <div class='loader--dot'></div>
    <div class='loader--dot'></div>
    <div class='loader--text'></div>
	
  </div>
  
</div>

        
    <div style="clear:both; display:none;"></div>
<h1>OneCheckout - Payment Ipay88 - Tester</h1>
<form action="<?php echo $url_payment;?>" method="post" name="ePayment" id="form_paymentku">
<table width="600" border="0" cellspacing="1" cellpadding="5">
  <tr>
    <td width="100" class="field_label">MerchantCode</td>
    <td width="500" class="field_input"><input name="MerchantCode" value="<?php echo $merchantCode;?>" type="text" id="MerchantCode" size="5" /></td>
  </tr>
  <tr>
    <td width="100" class="field_label">PaymentId</td>
    <td width="500" class="field_input"><input name="PaymentId" value="<?php echo $paymentId;?>" type="text" id="PaymentId" size="12" /></td>
  </tr>
  <tr>
    <td width="100" class="field_label">RefNo</td>
    <td width="500" class="field_input"><input name="RefNo" value="<?php echo $refno;?>" type="text" id="RefNo" size="12" /></td>
  </tr>
  <tr>
    <td class="field_label">Amount</td>
    <td class="field_input"><input name="Amount" type="text" value="<?php echo $amount;?>" id="Amount" size="12" /></td>
  </tr>
  <tr>
    <td class="field_label">CURRENCY</td>
    <td class="field_input"><input name="Currency" type="text" value="<?php echo $currency;?>" id="Currency" size="3" maxlength="3" /></td>
  </tr>
  
  <tr>
    <td class="field_label">ProdDesc</td>
    <td class="field_input"><input name="ProdDesc" type="text" id="ProdDesc" value="<?php echo $prodDesc;?>" size="100" /></td>
  </tr>
  <tr>
    <td class="field_label">UserName</td>
    <td class="field_input"><input name="UserName" type="text" id="UserName" value="<?php echo $userName;?>" size="12" /></td>
  </tr>
  <tr>
    <td class="field_label">UserEmail</td>
    <td class="field_input"><input name="UserEmail" value="<?php echo $userEmail;?>" type="text" id="UserEmail" size="16" /></td>
  </tr>
  
  <tr>
    <td class="field_label">UserContact</td>
    <td class="field_input"><input type="text" id="UserContact" value="<?php echo $userContact;?>" name="UserContact"  size="60" /></td>
  </tr>
  
  <tr>
    <td class="field_label">Remark</td>
    <td class="field_input"><input type="text" id="Remark" value="<?php echo $remark;?>" name="Remark"  size="60" /></td>
  </tr>
  <tr>
    <td class="field_label">Lang</td>
    <td class="field_input"><input name="Lang" type="text" id="Lang" value="UTF-8" maxlength="20" size="5" /></td>
  </tr>

  <tr>
    <td class="field_label">Signature</td>
    <td class="field_input"><input type="text" id="Signature" value="<?php echo $signature;?>" name="Signature" size="60" /></td>
  </tr>
  <tr>
    <td class="field_label">ResponseURL</td>
    <td class="field_input"><input type="text" id="ResponseURL" name="ResponseURL" value="<?php echo $ResponseURL;?>" size="60" /> </td>
  </tr>
  <tr>
    <td class="field_label">BackendURL</td>
    <td class="field_input"><input name="BackendURL" type="text" id="BackendURL" value="<?php echo $BackendURL;?>" size="60" /></td>
  </tr>
 

  <tr>
  	<td class="field_input" colspan="2">&nbsp;</td>
  </tr>
  
</table><br />
<input name="Submit" type="submit" class="bt_submit" id="submit_bro" value="SUBMIT" />
</form>
<?php }else{
	echo 'Transaksi anda sudah expired</p>';
}	?>
    </body>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script language="javascript" type="text/javascript"> 
  $(document).ready(function (e) {
    setTimeout($('#submit_bro').click(), 1000);
  });

</script
</html>