<?php defined( 'ABSPATH' ) || exit;

function init_GS_getnet_class(){

    class WC_Gateway_boletogetnet extends WC_Payment_Gateway {

       public function __construct(){

        $this->id = 'getnet-boleto';
        $this->has_fields = true;
        $this->method_title = __('Getnet - Boleto');
        $this->method_description = __('Integração de Boletos via API - Getnet Santander');

        $this->init_form_fields();
        $this->init_settings();

        $this->title 		 	= $this->get_option('title');
        $this->description 	 	= $this->get_option('description');
        $this->sandbox 	 	 	= ('yes' === $this->get_option('sandbox'));
		$this->seller_id 	 	= $this->sandbox ? $this->get_option('sandbox_seller_id') 		: $this->get_option('seller_id');
		$this->client_id 	 	= $this->sandbox ? $this->get_option('sandbox_client_id') 		: $this->get_option('client_id');
        $this->client_secret 	= $this->sandbox ? $this->get_option('sandbox_client_secret') 	: $this->get_option('client_secret');
        $this->api_url          = $this->sandbox ? 'https://api-sandbox.getnet.com.br/'         : 'https://api.getnet.com.br/';

        add_action ('woocommerce_update_options_payment_gateways_'. $this-> id, array ($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array( $this, 'order_summary_preview' ) );
        
        
       }

       public function init_form_fields(){

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => __('Habilitar'),
                    'label'       => __('Habilita ou Desabilita a forma de pagamento'),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => __('Nome que aparece pro cliente no finalizar pedido'),
                    'type'        => 'text',
                    'description' => __('Nome que aparece pro cliente no finalizar pedido'),
                    'default'     => __('Pagamento Via Boleto Getnet'),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __('Descrição'),
                    'type'        => 'textarea',
                    'description' => __('Informação extra mostrada na seleção da forma de pagamento'),
                    'default'     => __('Segurança via Getnet(Santander)'),
                ),
                'sandbox' => array(
                    'title'       => __('Sandbox'),
                    'label'       => __('Marque para usar versao de testes api Getnet (SANDBOX)'),
                    'type'        => 'checkbox',
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
                'sandbox_client_id' => array(
                    'title'       => __('SANDBOX: Client ID'),
                    'type'        => 'text',
                    'class'	      => 'sandbox'
                ),
                'sandbox_client_secret' => array(
                    'title'       => __('SANDBOX: Client Secret'),
                    'type'        => 'text',
                    'class'	      => 'sandbox'
                ),
                'sandbox_seller_id' => array(
                    'title'       => __('SANDBOX: Seller ID'),
                    'type'        => 'text',
                    'class'	      => 'sandbox'
                ),
                'client_id' => array(
                    'title'       => __('Client ID'),
                    'type'        => 'text',
                    'class'	      => 'production'		
                ),
                'client_secret' => array(
                    'title'       => __('Client Secret'),
                    'type'        => 'text',
                    'class'	      => 'production'		
                ),
                'seller_id' => array(
                    'title'       => __('Seller ID'),
                    'type'        => 'text',
                    'class'	      => 'production'		
                )
            );
        }

        //CAMPO EMBAIXO DA OPÇÃO DE PAGAMENTO PARA DESCRIÇÕES.
        function payment_fields()
        {
            
            if(!empty($this->description)) {
                echo wpautop(trim(sanitize_text_field($this->description)));
            }
            if ($this->sandbox){ 
                echo wpautop(__('</br><p>Modo de testes (SANDBOX). Pagamentos e Boletos Gerados não validos.</p>'));
            }
            
        }

        // FUNÇÃO PADRAO DO WOOCOMMERCE PARA PROCESSO DO PAGAMENTO
        function process_payment( $order_id ) {

            global $woocommerce;
            $order = new WC_Order( $order_id );

            //IMPORTA COMPOSER
            require __DIR__ . '/vendor/autoload.php';

            //Parte da API\/\/\/

            include("token.php");


            /* Criando um client (Ele é a classe que faz as requisições ) API*/
            /* base_uri URL BASE */
            $client = new \GuzzleHttp\Client([
                'base_uri' => $this->api_url, 
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,        
                    'Content-Type' => 'application/json',
                ]
            ]);

            //VERIFICA O TIPO DE DOCUMENTO "CPF OU CNPJ"
            $document_type = $order->billing_persontype;
            if ( $document_type == 1 ){
                $document_type = "CPF";
            }else{
                $document_type = "CNPJ";
            }

            // VERIFICA E RETIRA " - " do CEP
            $number_postal_code = preg_replace('/[^0-9]/', '', $order->get_billing_postcode());

            $number_amount = preg_replace('/[^0-9]/', '', $order->get_total());

            //CONCATENAÇÃO NOME COMPLETO
            $first_name = $order->get_billing_first_name();
            $last_name = $order->get_billing_last_name();
            $name_full = $first_name . " " . $last_name;

            /* AQUI UM ARRAY, CONTEUDO PASSADO PRA API */
            $body = [
                'seller_id' => $this->seller_id,
                'amount'    => $number_amount,
                'order'     => [
                    'order_id' => (string) $order_id
                ],
                'boleto'    => [
                    'document_number' => (string) $order_id,
                ],
                'customer' => [
                    'customer_id' 		=> (string) $order->get_customer_id(),
                    'name'		=> $name_full,
                    'document_type' => $document_type,
                    'document_number' => $order->billing_cpf,
                    'billing_address' 	=> [
                        'street' => $order->get_billing_address_1(),
                        'number' => $order->billing_number,
                        'complement' => $order->get_billing_address_2(),
                        'district' => $order->billing_neighborhood,
                        'city' => $order->get_billing_city(),
                        'state' => $order->get_billing_state(),
                        'postal_code' => $number_postal_code,
                    ],
                ],
                
            ];

            /*  TIPO METODO E NOME DO METODO API*/
            $response = $client->post('/v1/payments/boleto', [
                \GuzzleHttp\RequestOptions::JSON => $body
            ]);
            
            //RETORNO DA COMUNICAÇÃO
            $corpoRetorno = json_decode($response->getBody()->getContents());
            
            $res = $corpoRetorno->{'status'};

            //PEGA O LINK DO BOLETO NO RETORNO
            $linkHtml = $corpoRetorno->{'boleto'}->{'_links'};
            $filtroHref = array_column($linkHtml, 'href');
            $selectHrefPdf  = $filtroHref[0];
            $selectHrefHtml = $filtroHref[1];
            
            //ADICIONA O CONTEUDO DA ARRAY EM UM METADATA PARA SER LIDO NA PROXIMA PAGINA
            $order->add_meta_data('Boleto_URL', $selectHrefHtml, true);
            $order->add_meta_data('Boleto_PDF', $selectHrefPdf, true);
            $order->add_meta_data('Boleto_Email_Link', $this->api_url, true);

             
            // VERIFICA SE O BOLETO FOI GERADO COM SUCESSO E RETONA PEDIDO CONCLUIDO P/ WOOCOMMERCE.
            if($res == 'PENDING') {
                $order->update_status('on-hold', __( 'Aguardando pagamento do boleto'));
                // wc_reduce_stock_levels($order); UTILIZAR SE PRECISA QUE BAIXA O ESTOQUE
                // $order->add_order_note(__('Pedido recebido aguardando pagamento do boleto'));
                $woocommerce->cart->empty_cart();
                
    
                $status['result'] = 'success';
                $status['redirect'] =  $this->get_return_url( $order );
            }
    
            return $status;

        }
        

        // EXIBE BOLETO DEPOIS DE GERADO NA PROXIMA PAGINA
        function order_summary_preview( $order_id ) {
		
    
            $order = wc_get_order( $order_id );

            //PUXA CONTEÚDO DA PAGINA DO PAGAMENTO PARA PAGINA DE PEDIDO FINALIZADO.
            $urlBoletoHtml = $order->get_meta('Boleto_URL');
            $urlBoletoPdf = $order->get_meta('Boleto_PDF');

            //EXIBE IFRAME E BOLETO PARA DOWNLOAD.
            $html = '<p></p>';
            $html = '<p>' . __( 'Por favor, pague o boleto para que sua compra seja aprovada.', 'woo-cielo-boleto' ) .' <a href="'. $this->api_url . $urlBoletoPdf .'">Baixar Boleto em PDF</a></p>';
            $html .= '<p><iframe src="'. $this->api_url . $urlBoletoHtml .'" style="width:100%; height:1000px;border: solid 1px #eee;"></iframe></p>';
             
            echo '<p>' . $html . '</p>';
            		
        }
        
        
    }
}

// INFORMA AO WOOCOMMERCE QUE EXISTE UMA NOVA FUNÇÃO
function GS_boleto_getnet( $methods ) {
    $methods[] = 'WC_Gateway_boletogetnet'; 
    return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'GS_boleto_getnet' );