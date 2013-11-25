<?php

function uc_cavirtex_complete($cart_id = 0) {

  $txid = check_plain($_GET['PAYMENT_BATCH_NUM']);
  for ($i = 1; $i <= 10; $i++) {
    if (db_query("SELECT COUNT(txid) FROM {uc_payment_cavirtex} WHERE txid = :s", array(':s' => $txid))->fetchField()) {break;}
	sleep (3);
  }
  watchdog('Canadian Virtual Exchange', 'Buyer returned back with payment completed status');
  $order = uc_order_load($_GET['PAYMENT_ID']);
  $description = '';
  $order = uc_order_load((int)$_GET['PAYMENT_ID']);
  $order_state = uc_order_status_data($order->order_status, 'state');
  uc_order_save($order);
  watchdog('Canadian Virtual Exchange', 'Payment approved for order #!order_id.', array('!order_id' => check_plain($_GET['PAYMENT_ID'])));
  $comment = t('Paid with Bitcoin.');
  uc_payment_enter($order->order_id, 'cavirtex', $_GET['PAYMENT_AMOUNT'], 0, NULL, $comment);
  uc_cart_complete_sale($order);
  drupal_set_message(t('Your Bitcoins have been received and your order will be processed soon.'));
  $description = 'Approved.';
  $txid = check_plain($_GET['PAYMENT_BATCH_NUM']);
  $order_id = check_plain($_GET['PAYMENT_ID']);
  $amount = check_plain($_GET['PAYMENT_AMOUNT']);
  if (db_query("SELECT COUNT(txid) FROM {uc_payment_cavirtex} WHERE txid = :s", array(':s' => $txid))->fetchField()) {
    db_query("UPDATE {uc_payment_cavirtex} SET description = :s WHERE txid = :d",
	  array(
	    ':s' => $description . ' update',
	    ':d' => $txid
	  )
	);
  }
  else {
    db_insert('uc_payment_cavirtex')
      ->fields(array(
        'txid' => $txid,
        'order_id' => $order_id,
        'amount' => $amount,
        'description' => $description
      ))
      ->execute();
  }
    
  if (!isset($_SESSION['cart_order'])){
	drupal_goto('cart');
  }
  $_SESSION['uc_checkout'][$_SESSION['cart_order']]['do_complete'] = TRUE;
  drupal_goto('cart/checkout/complete');
}

function uc_cavirtex_failed($cart_id = 0) {
  watchdog('Canadian Virtual Exchange', 'Payment cancelled by Canadian Virtual Exchange or buyer');
  $order = uc_order_load($_GET['PAYMENT_ID']);
  if (uc_order_status_data($order->order_status, 'state') == 'in_checkout') {
    drupal_set_message(t('Your payment was cancelled. Please try again or change the payment method.'));
    uc_order_comment_save($order->order_id, 0, t('Payment was cancelled at Canadian Virtual Exchange.'), 'admin');
  }
  drupal_goto('cart/checkout');
  return $output;
}



