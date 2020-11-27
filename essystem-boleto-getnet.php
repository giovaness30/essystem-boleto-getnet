<?php defined( 'ABSPATH' ) || exit;
/**
 * Plugin Name:          Essystem - Boleto via Getnet
 * Description:          Habilita modo de pagameto via boleto da Getnet
 * Author:               Giovane Sedano
 * Author URI:           https://github.com/giovaness30
 * Version:              1.0.3
 * License:              GPLv3 or later
 * Text Domain: 		 essystem-boleto-getnet
 *
 * Essystem - Plugin desenvolvido para utilizar emissao de boletos via API Getnet nos E-commerce para os clientes da empresa.
 */

if ( ! class_exists( 'GS_boleto_getnet' ) ) {
	require_once __DIR__ . '/class.GS_boleto_getnet.php';
	add_action( 'plugins_loaded', 'init_GS_getnet_class' );

	//ADICIONA LINK ATALHO CONFIGURAÇÕES DO PLUGIN.
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );
	function add_action_links ( $links ) {
	$mylinks = array(
	'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=getnet-boleto' ) . '">Configurações</a>',
	);
	return array_merge( $links, $mylinks );
	}

	add_action( 'woocommerce_email_order_details','add_order_email_instructions', 10, 4 );
	function add_order_email_instructions( $order, $sent_to_admin, $plain_text, $email ) {
		if ( $order->status == 'on-hold' && $order->get_payment_method() == 'getnet-boleto') {
			echo '<strong>Para re-imprimir o boleto <a href="'. $order->get_meta('Boleto_Email_Link') . $order->get_meta('Boleto_PDF') .'"> clique aqui!</a></strong>';
		} 
	}
	
}