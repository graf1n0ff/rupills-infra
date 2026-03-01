<?php
// Add custom Theme Functions here

// Хелпер: получает бренд товара (Brand → Manufacturer → WPML cross-language fallback)
function rupills_get_product_brand( $product ) {
    if ( ! $product ) return '';
    $lang = apply_filters( 'wpml_current_language', 'en' );

    // 1) Прямой атрибут Brand / Бренд
    $brand = ( $lang === 'en' )
        ? $product->get_attribute( 'Brand' )
        : $product->get_attribute( 'Бренд' );
    if ( ! empty( $brand ) ) return $brand;

    // 2) Фоллбек: Manufacturer / Производитель → берём компанию до запятой
    $manufacturer = ( $lang === 'en' )
        ? $product->get_attribute( 'Manufacturer' )
        : $product->get_attribute( 'Производитель' );
    if ( ! empty( $manufacturer ) ) {
        $brand = rupills_extract_company_from_manufacturer( $manufacturer );
        if ( ! empty( $brand ) ) return $brand;
    }

    // 3) WPML: если у текущей языковой версии пусто — берём из другой
    if ( function_exists( 'icl_object_id' ) ) {
        $other_lang  = ( $lang === 'en' ) ? 'ru' : 'en';
        $other_id    = icl_object_id( $product->get_id(), 'product', false, $other_lang );
        if ( $other_id && $other_id !== $product->get_id() ) {
            $other_product = wc_get_product( $other_id );
            if ( $other_product ) {
                $brand = ( $other_lang === 'en' )
                    ? $other_product->get_attribute( 'Brand' )
                    : $other_product->get_attribute( 'Бренд' );
                if ( ! empty( $brand ) ) return $brand;

                $mfr = ( $other_lang === 'en' )
                    ? $other_product->get_attribute( 'Manufacturer' )
                    : $other_product->get_attribute( 'Производитель' );
                if ( ! empty( $mfr ) ) {
                    $brand = rupills_extract_company_from_manufacturer( $mfr );
                    if ( ! empty( $brand ) ) return $brand;
                }
            }
        }
    }

    return '';
}

function rupills_extract_company_from_manufacturer( $manufacturer ) {
    $parts   = explode( ',', $manufacturer );
    $company = trim( $parts[0] );
    $skip    = array( 'Russia', 'Israel', 'India', 'USA', 'China', 'Germany',
        'Россия', 'Израиль', 'Индия', 'США', 'Китай', 'Германия' );
    if ( in_array( $company, $skip, true ) ) return '';
    return $company;
}
/*
// картинка по умолчанию, когда нет фото, сначала загрузить через стандартное добавление фото
add_action( 'init', 'custom_fix_thumbnail' );
  
function custom_fix_thumbnail() {
  add_filter('woocommerce_placeholder_img_src', 'custom_woocommerce_placeholder_img_src');
    
    function custom_woocommerce_placeholder_img_src( $src ) {
    $upload_dir = wp_upload_dir();
    $uploads = untrailingslashit( $upload_dir['baseurl'] );
    $src = $uploads . '/2015/08/default-image.jpg';
      
    return $src;
    }
}
*/
//end

// удаление категории "Без категории"
//* Used when the widget is displayed as a dropdown
add_filter( 'woocommerce_product_categories_widget_dropdown_args', 'rv_exclude_wc_widget_categories' );
//* Used when the widget is displayed as a list
add_filter( 'woocommerce_product_categories_widget_args', 'rv_exclude_wc_widget_categories' );
function rv_exclude_wc_widget_categories( $cat_args ) {
  $cat_args['exclude'] = array('15'); // здесь ID вашей категории
  return $cat_args;
}
// удаление категории "Без категории"

// скрывает SKU 
//add_filter( 'wc_product_sku_enabled', '__return_false' );
//-----------

//--- отключение стандартных размеров, woocommerce надеюсь не затронет
function dco_remove_default_image_sizes( $sizes) {
	return array_diff( $sizes, array(
		'thumbnail',
		'medium',
		'medium_large',
		'large',
	) );
}
add_filter('intermediate_image_sizes', 'dco_remove_default_image_sizes');
//---------

//------ делает вкладуку до информация последней
add_filter( 'woocommerce_product_tabs', 'woo_reorder_tabs', 98 );
function woo_reorder_tabs( $tabs ) {
 	//$tabs['reviews']['priority'] = 5;			// Reviews first
	//$tabs['description']['priority'] = 10;			// Description second
	if ( isset( $tabs['additional_information'] ) ) {
		$tabs['additional_information']['priority'] = 99;	// Additional information third
	}
	return $tabs;
}
//------

//------ отключаем все автообновления
if( is_admin() ){
	// отключим проверку обновлений при любом заходе в админку...
	remove_action( 'admin_init', '_maybe_update_core' );
	remove_action( 'admin_init', '_maybe_update_plugins' );
	remove_action( 'admin_init', '_maybe_update_themes' );

	// отключим проверку обновлений при заходе на специальную страницу в админке...
	remove_action( 'load-plugins.php', 'wp_update_plugins' );
	remove_action( 'load-themes.php', 'wp_update_themes' );

	// оставим принудительную проверку при заходе на страницу обновлений...
	remove_action( 'load-update-core.php', 'wp_update_plugins' );
	remove_action( 'load-update-core.php', 'wp_update_themes' );

	// внутренняя страница админки "Update/Install Plugin" или "Update/Install Theme" - оставим не мешает...
	remove_action( 'load-update.php', 'wp_update_plugins' );
	remove_action( 'load-update.php', 'wp_update_themes' );

	// событие крона не трогаем, через него будет проверяться наличие обновлений - тут все отлично!
	remove_action( 'wp_version_check', 'wp_version_check' );
	remove_action( 'wp_update_plugins', 'wp_update_plugins' );
	remove_action( 'wp_update_themes', 'wp_update_themes' );

	/**
	 * отключим проверку необходимости обновить браузер в консоли - мы всегда юзаем топовые браузеры!
	 * эта проверка происходит раз в неделю...
	 * @see https://wp-kama.ru/function/wp_check_browser_version
	 */
	add_filter( 'pre_site_transient_browser_'. md5( $_SERVER['HTTP_USER_AGENT'] ), '__return_empty_array' );
}
//-------



//disable zxcvbn.min.js in wordpress проверка паролей
add_action('wp_print_scripts', 'remove_password_strength_meter');
function remove_password_strength_meter() {
    // Deregister script about password strenght meter
    wp_dequeue_script('zxcvbn-async');
    wp_deregister_script('zxcvbn-async');
}
//-------

/* ------------------ позволяет редактировать товары в статусе "Обработка" ----------------- */
add_filter( 'wc_order_is_editable', 'lets_make_processing_orders_editable', 10, 2 );
function lets_make_processing_orders_editable( $is_editable, $order ) {
    if ( $order->get_status() == 'processing' or 'wc-reserved') {
        $is_editable = true;
    }
 
    return $is_editable;
}

// Store cart weight in the database
add_action('woocommerce_checkout_update_order_meta', 'woo_add_cart_weight');

function woo_add_cart_weight( $order_id ) {
    global $woocommerce;
    
    $weight = $woocommerce->cart->cart_contents_weight;
    update_post_meta( $order_id, '_cart_weight', $weight );
}

// Add order new column in administration
add_filter( 'manage_edit-shop_order_columns', 'woo_order_weight_column', 20 );

function woo_order_weight_column( $columns ) {

  $offset = 8;
  $updated_columns = array_slice( $columns, 0, $offset, true) +
  array( 'total_weight' => esc_html__( 'Weight', 'woocommerce' ) ) +
  array_slice($columns, $offset, NULL, true);

  return $updated_columns;
}

// Populate weight column
add_action( 'manage_shop_order_posts_custom_column', 'woo_custom_order_weight_column', 2 );

function woo_custom_order_weight_column( $column ) {
  global $post;
 
  if ( $column == 'total_weight' ) {
    $weight = get_post_meta( $post->ID, '_cart_weight', true );
    if ( $weight > 0 )
      print $weight . ' ' . esc_attr( get_option('woocommerce_weight_unit' ) );
    else print 'N/A';
  }
}

add_action( 'woocommerce_admin_order_totals_after_total', 'action_function_name_9670' );
function action_function_name_9670( $order_id ){
	global $post;
	$weight = get_post_meta( $post->ID, '_cart_weight', true );
	print $weight . ' ' . esc_attr( get_option('woocommerce_weight_unit' ) );
}
/*----------------------------------------------------*/

/*---------- Отключает проверки WPML: поиск и список заказов работают на всех языках ---*/
add_action( 'pre_get_posts', 'rupills_woo_orders_admin_all_languages', 1 );
function rupills_woo_orders_admin_all_languages( $query ) {
	if ( ! is_admin() ) {
		return;
	}
	$post_type = $query->get( 'post_type' );
	// Список заказов (с поиском и без) — показывать заказы всех языков
	if ( $post_type === 'shop_order' ) {
		$query->set( 'suppress_filters', true );
		return;
	}
	// Остальной поиск (товары, посты и т.д.) — без фильтра по языку
	if ( $query->is_search() ) {
		$query->set( 'suppress_filters', true );
	}
}

/*---------- Поиск заказов по названиям товаров на всех языках WPML ----------------*/
if ( ! function_exists( 'rupills_wpml_product_titles_for_order_search' ) ) {
	/**
	 * Собирает все варианты названий товаров (по языкам WPML) для поиска заказов.
	 * Если искать "Dikul", вернёт ["Dikul","Дикуль"] и наоборот.
	 */
	function rupills_wpml_product_titles_for_order_search( $term ) {
		$term = wc_clean( $term );
		if ( empty( $term ) || strlen( $term ) < 2 ) {
			return array( $term );
		}
		if ( ! function_exists( 'icl_object_id' ) || ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return array( $term );
		}
		global $wpdb;
		$tr_ids_table = $wpdb->prefix . 'icl_translations';
		$like         = '%' . $wpdb->esc_like( $term ) . '%';

		// trid всех товаров, у которых title (в любом языке) содержит термин
		$trids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT t.trid
				FROM {$wpdb->posts} p
				INNER JOIN {$tr_ids_table} t ON t.element_id = p.ID AND t.element_type = 'post_product'
				WHERE p.post_type = 'product' AND p.post_status IN ('publish','private','draft')
				AND p.post_title LIKE %s",
				$like
			)
		);
		if ( empty( $trids ) ) {
			return array( $term );
		}
		$trids_placeholders = implode( ',', array_fill( 0, count( $trids ), '%d' ) );

		// Все post_title товаров из этих trid (все языки)
		$titles = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.post_title
				FROM {$wpdb->posts} p
				INNER JOIN {$tr_ids_table} t ON t.element_id = p.ID AND t.element_type = 'post_product'
				WHERE t.trid IN ({$trids_placeholders})
				AND p.post_title != ''",
				...$trids
			)
		);
		if ( empty( $titles ) ) {
			return array( $term );
		}
		$titles = array_map( 'wc_clean', array_filter( array_unique( $titles ) ) );
		$all    = array_unique( array_merge( array( $term ), $titles ) );
		return array_slice( $all, 0, 25 );
	}
}

add_filter( 'woocommerce_shop_order_search_results', 'rupills_woo_order_search_add_wpml_product_names', 10, 3 );
function rupills_woo_order_search_add_wpml_product_names( $order_ids, $term, $search_fields ) {
	$titles = rupills_wpml_product_titles_for_order_search( $term );
	if ( count( $titles ) <= 1 ) {
		return $order_ids;
	}
	global $wpdb;
	$order_items_table = $wpdb->prefix . 'woocommerce_order_items';
	$parts            = array();
	$values           = array();
	foreach ( $titles as $i => $t ) {
		if ( $t === $term ) {
			continue;
		}
		$parts[]  = 'order_item_name LIKE %s';
		$values[] = '%' . $wpdb->esc_like( $t ) . '%';
	}
	if ( empty( $parts ) ) {
		return $order_ids;
	}
	$sql    = "SELECT order_id FROM {$order_items_table} WHERE " . implode( ' OR ', $parts );
	$extra  = $wpdb->get_col( $wpdb->prepare( $sql, ...$values ) );
	$merged = array_unique( array_merge( array_map( 'intval', (array) $order_ids ), array_map( 'absint', $extra ) ) );
	return array_values( $merged );
}

add_filter( 'woocommerce_cot_shop_order_search_results', 'rupills_woo_order_search_add_wpml_product_names_cot', 10, 2 );
function rupills_woo_order_search_add_wpml_product_names_cot( $order_ids, $term ) {
	$titles = rupills_wpml_product_titles_for_order_search( $term );
	if ( count( $titles ) <= 1 ) {
		return $order_ids;
	}
	$found = array_map( 'intval', (array) $order_ids );
	foreach ( $titles as $t ) {
		if ( $t === $term ) {
			continue;
		}
		$more = wc_get_orders( array( 's' => $t, 'return' => 'ids', 'limit' => 500 ) );
		$found = array_unique( array_merge( $found, array_map( 'intval', (array) $more ) ) );
	}
	return array_values( $found );
}
//-----------

/* --------------- включение кеширования Sitemap -----------------*/
add_filter( 'wpseo_enable_xml_sitemap_transient_caching', '__return_true' );
/* --------------- включение кеширования Sitemap -----------------*/

/* не более 10 позиций в одном заказе */
// Checking and validating when products are added to cart
add_filter( 'woocommerce_add_to_cart_validation', 'only_six_items_allowed_add_to_cart', 10, 3 );

function only_six_items_allowed_add_to_cart( $passed, $product_id, $quantity ) {

    $cart_items_count = WC()->cart->get_cart_contents_count();
    $total_count = $cart_items_count + $quantity;

    if( $cart_items_count >= 10 || $total_count > 10 ){
        // Set to false
        $passed = false;
        // Display a message
         wc_add_notice( __( "You can’t have more than 10 items in cart. If you need more then make an additional order.", "woocommerce" ), "error" );
    }
    //if($product->get_attribute('recept') == 'strogo_psy' ) $prod_simple = true;
      //  {
        //  wc_add_notice( __( "test text", "woocommerce" ), "error" );
        //}
    return $passed;
}

// Checking and validating when updating cart item quantities when products are added to cart
add_filter( 'woocommerce_update_cart_validation', 'only_six_items_allowed_cart_update', 10, 4 );
function only_six_items_allowed_cart_update( $passed, $cart_item_key, $values, $updated_quantity ) {

    $cart_items_count = WC()->cart->get_cart_contents_count();
    $original_quantity = $values['quantity'];
    $total_count = $cart_items_count - $original_quantity + $updated_quantity;

    if( $cart_items_count > 10 || $total_count > 10 ){
        // Set to false
        $passed = false;
        // Display a message
         wc_add_notice( __( "You can’t have more than 10 items in cart. If you need more then make an additional order.", "woocommerce" ), "error" );
    }
     

    return $passed;
}
/* не более 10 позиций в одном заказе */

/* Меняет сообщение когда товары не найдены */
add_action( 'woocommerce_no_products_found', function(){
  remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );

  $lang = apply_filters( 'wpml_current_language', 'en' );

  if ( $lang === 'ru' ) {
    $message = '
<h2>🤷‍♂️ По вашему запросу ничего не найдено</h2>
<ul style="margin-left: 20px;">
<li>Возможно, вы допустили ошибку в написании: «супрастин» вместо «супрасин».</li>
<li>Попробуйте ввести запрос на другом языке — возможно, произошла ошибка перевода.</li>
<li>Попробуйте упростить запрос: «боль в спине» вместо «мазь от боли в спине».</li>
<li>📱 Некоторые товары доступны <strong>эксклюзивно в нашем приложении</strong>. <a href="/app/">Установите приложение Ru-Pills</a>, чтобы получить доступ к полному каталогу!</li>
<li>Если нужна помощь, напишите в <!--noindex--><a href="https://t.me/Ru_pills" rel="nofollow">Telegram @Ru_pills</a><!--/noindex--></li></ul>';
  } else {
    $message = '
<h2>🤷‍♂️ Nothing was found by the query</h2>
<ul style="margin-left: 20px;">
<li>You may have misspelled words: "suprsatin" instead of "suprastin".</li>
<li>Try typing in another language, perhaps a translation error.</li>
<li>Try simplifying your query: "back pain" instead of "back pain ointment".</li>
<li>📱 Some products are <strong>exclusively available in our app</strong>. <a href="/app/">Install the Ru-Pills app</a> to access the full catalog!</li>
<li>If you need help, please write to <!--noindex--><a href="https://t.me/Ru_pills" rel="nofollow">Telegram @Ru_pills</a><!--/noindex--></li></ul>';
  }

  echo '<p class="woocommerce-info">' . $message . '</p>';

  if ( function_exists( 'relevanssi_didyoumean' ) ) {
      $search_query = get_search_query();
      $lang         = apply_filters( 'wpml_current_language', 'en' );
      $pre_text     = ( $lang === 'ru' ) ? 'Возможно, вы искали: ' : 'Did you mean: ';
      relevanssi_didyoumean( $search_query, '<p class="relevanssi-didyoumean" style="font-size:1.1em;margin:1em 0;">' . $pre_text, '</p>', 5, true );
  }
}, 9 );
/* Меняет сообщение когда товары не найдены */

/* Скрываем РЕФЕРЕР на странице CHECKOUT */

add_action( 'wp_head', 'head_noref_meta_tags' );
function head_noref_meta_tags(){
	if ( is_checkout() ) {
	echo '<meta name="referrer" content="no-referrer">';
}
}
/* Скрываем РЕФЕРЕР на странице CHECKOUT */

// ***************************** Добавляем + и текст к номеру телефона 
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

// Наша перехваченная функция - $fields проходит через фильтр!
function custom_override_checkout_fields( $fields ) {
     $fields['billing']['billing_phone']['placeholder'] = 'International number with country code';
     return $fields;
	 //print_r($fields);
}
// ***************************** Добавляем + и текст к номеру телефона

// ************* товары которых нет в наличии отправлять вниз **************

class iWC_Orderby_Stock_Status{
  public function __construct(){
      if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
      add_filter('posts_clauses', array($this, 'order_by_stock_status'), 2000);
  }
  }
  public function order_by_stock_status($posts_clauses){
  global $wpdb;
  if (is_woocommerce() && (is_shop() || is_product_category() || is_product_tag())) {
      $posts_clauses['join'] .= " INNER JOIN $wpdb->postmeta istockstatus ON ($wpdb->posts.ID = istockstatus.post_id) ";
      $posts_clauses['orderby'] = " istockstatus.meta_value ASC, " . $posts_clauses['orderby'];
      $posts_clauses['where'] = " AND istockstatus.meta_key = '_stock_status' AND istockstatus.meta_value <> '' " . $posts_clauses['where'];
  }
  return $posts_clauses;
  }
  }
  new iWC_Orderby_Stock_Status;
  
  // ************* товары которых нет в наличии отправлять вниз **************

// ----------------- кликабельный URL на почту для фото ---------------------
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'admin_custom_row_after_order_addresses', 10, 1 );
function admin_custom_row_after_order_addresses( $order ){
	$order_id   = $order->get_id();
	$email      = $order->get_billing_email();
	$first_name = $order->get_billing_first_name();

	$lang = $order->get_meta( 'wpml_language' );
	if ( empty( $lang ) ) {
		$lang = get_post_meta( $order_id, 'wpml_language', true );
	}
	if ( empty( $lang ) ) {
		$lang = 'en';
	}

	if ( $lang === 'ru' ) {
		$subject = '[Ru-Pills.com] Ваш заказ #' . $order_id . ' готов к отправке';
		$body = "Здравствуйте, {$first_name}!\n\nВаш заказ готов к отправке. Фотография вашего заказа прикреплена к этому письму. Последующее письмо с номером для отслеживания будет отправлено вам в ближайшее время.\n\nБольшое спасибо за то, что сделали покупку у нас.\nКоманда Ru-Pills.com";
	} else {
		$subject = '[Ru-Pills.com] Your order #' . $order_id . ' is ready to ship';
		$body = "Hello, {$first_name}!\n\nYour order is ready to be shipped. A picture of your order is attached to this email. A follow up email with a tracking number will be sent to you shortly.\n\nThank you so much for making a purchase from us.\nTeam Ru-Pills.com";
	}

	$mailto     = 'mailto:' . esc_attr( $email ) . '?subject=' . rawurlencode( $subject ) . '&body=' . rawurlencode( $body );
	$photo_sent = $order->get_meta( '_photo_sent' );
	$sent_label = '';
	if ( $photo_sent ) {
		$sent_label = '<span style="color:#28a745;font-weight:600;margin-left:10px;">📷 Отправлено ' . esc_html( $photo_sent ) . '</span>';
	}
	?>
	</div></div>
	<div class="clear"></div>
	<div class="order_data_column_container">
		<div class="order_data_column_wide">
			<h3>
				<a href="<?php echo esc_url( $mailto ); ?>"
				   id="rupills-photo-mail-link"
				   data-order-id="<?php echo esc_attr( $order_id ); ?>"
				   data-nonce="<?php echo esc_attr( wp_create_nonce( 'rupills_photo_sent_' . $order_id ) ); ?>"
				   ><?php echo $photo_sent ? '📷 Фото на почту' : 'Фото на почту'; ?> <?php echo esc_html( $email ); ?></a>
				<?php echo $sent_label; ?>
			</h3>
	<?php
}

add_action( 'admin_footer', 'rupills_photo_sent_js' );
function rupills_photo_sent_js() {
	$screen = get_current_screen();
	if ( ! $screen ) return;
	if ( $screen->id !== 'shop_order' && $screen->id !== 'woocommerce_page_wc-orders' ) return;
	?>
	<script>
	(function(){
		var link = document.getElementById('rupills-photo-mail-link');
		if (!link) return;
		link.addEventListener('click', function(e){
			e.preventDefault();
			var href = this.href;
			var orderId = this.dataset.orderId;
			var nonce = this.dataset.nonce;
			fetch(ajaxurl, {
				method: 'POST',
				headers: {'Content-Type':'application/x-www-form-urlencoded'},
				body: 'action=rupills_mark_photo_sent&order_id=' + orderId + '&nonce=' + nonce
			}).then(function(r){ return r.json(); }).then(function(data){
				if (data.success) {
					var span = link.parentNode.querySelector('span');
					if (!span) {
						span = document.createElement('span');
						span.style.cssText = 'color:#28a745;font-weight:600;margin-left:10px;';
						link.parentNode.appendChild(span);
					}
					span.textContent = '📷 Отправлено ' + data.data.date;
					link.textContent = '📷 Фото на почту ' + link.textContent.replace(/^📷\s*/, '').replace(/^Фото на почту\s*/, '');
				}
				window.location.href = href;
			}).catch(function(){
				window.location.href = href;
			});
		});
	})();
	</script>
	<?php
}

add_action( 'wp_ajax_rupills_mark_photo_sent', 'rupills_mark_photo_sent_ajax' );
function rupills_mark_photo_sent_ajax() {
	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$nonce    = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
	if ( ! $order_id || ! wp_verify_nonce( $nonce, 'rupills_photo_sent_' . $order_id ) ) {
		wp_send_json_error( 'invalid nonce' );
	}
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		wp_send_json_error( 'no permission' );
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		wp_send_json_error( 'order not found' );
	}
	$now = current_time( 'd.m.Y H:i' );
	$order->update_meta_data( '_photo_sent', $now );
	$order->save();
	wp_send_json_success( array( 'date' => $now ) );
}
// ----------------- кликабельный URL на почту для фото ---------------------



//-------- Убираем кнопку отменить и оплатить в заказах ---------------
/*add_filter('woocommerce_my_account_my_orders_actions', 'remove_my_cancel_button', 10, 2);
function remove_my_cancel_button( $actions, $order ){
    unset($actions['cancel']);
		unset($actions['pay']);
        return $actions;
}*/
//-------- Убираем кнопку отменить и оплатить в заказах ---------------

//------------ свой статус заказа ------------------------------------------------

add_action( 'init', 'register_my_new_order_statuses' );

function register_my_new_order_statuses() {
    register_post_status( 'wc-reserved', array(
        'label'                     => _x( 'Packing', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Packing <span class="count">(%s)</span>', 'Packing<span class="count">(%s)</span>', 'woocommerce' )
    ) );
}

add_filter( 'wc_order_statuses', 'my_new_wc_order_statuses' );

// Register in wc_order_statuses.
function my_new_wc_order_statuses( $order_statuses ) {
    $order_statuses['wc-reserved'] = _x( 'Packing', 'Order status', 'woocommerce' );

    return $order_statuses;
}

add_action('admin_head', 'styling_admin_order_list' );
function styling_admin_order_list() {
    global $pagenow, $post;

    if( $pagenow != 'edit.php') return; // Exit
    if( get_post_type($post->ID) != 'shop_order' ) return; // Exit

    // HERE we set your custom status
    $order_status = 'Reserved'; // <==== HERE
    ?>
    <style>
        .order-status.status-<?php echo sanitize_title( $order_status ); ?> {
            background: #EE8FFF;
            
        }
    </style>
    <?php
}
//------------ свой статус заказа ------------------------------------------------

//-------------- email о смене статуса заказа ------------------ //

add_action("woocommerce_order_status_changed", "my_custom_notification");

function my_custom_notification($order_id, $checkout=null) {
   $order = wc_get_order($order_id);
   if ( ! $order || $order->get_status() !== 'reserved' ) {
      return;
   }

   $lang = $order->get_meta('wpml_language');
   if ( empty( $lang ) ) {
      $lang = get_post_meta( $order_id, 'wpml_language', true );
   }
   if ( empty( $lang ) ) {
      $lang = 'en';
   }

   $mailer = WC()->mailer();
   $order_number = $order->get_order_number();
   $first_name  = $order->get_billing_first_name();

   if ( $lang === 'ru' ) {
      $subject = sprintf( '[Ru-Pills.com] Заказ #%s передан на упаковку', $order_number );
      $heading = sprintf( 'Заказ #%s передан на упаковку', $order_number );
      $message_body = sprintf(
         '<p style="font-size:15px;color:#333;line-height:1.6;margin:0 0 16px;">Здравствуйте, <strong>%s</strong>!</p>'

         . '<div style="background:#eaf7ec;border-left:4px solid #4caf50;padding:14px 18px;border-radius:4px;margin-bottom:20px;">'
         .   '<p style="margin:0;font-size:15px;color:#2e7d32;line-height:1.5;">'
         .     '&#10003; Ваш заказ <strong>#%s</strong> покинул аптеку и передан в отдел упаковки.'
         .   '</p>'
         . '</div>'

         . '<div style="background:#fff3e0;border-left:4px solid #ff9800;padding:14px 18px;border-radius:4px;margin-bottom:20px;">'
         .   '<p style="margin:0 0 6px;font-size:14px;color:#e65100;line-height:1.5;font-weight:bold;">'
         .     '&#9888; Важная информация'
         .   '</p>'
         .   '<p style="margin:0;font-size:14px;color:#555;line-height:1.5;">'
         .     'С этого момента заказ находится в работе — <strong>отменить, изменить состав или адрес доставки уже невозможно</strong>.'
         .   '</p>'
         . '</div>'

         . '<div style="background:#e3f2fd;border-left:4px solid #2196f3;padding:14px 18px;border-radius:4px;margin-bottom:20px;">'
         .   '<p style="margin:0 0 6px;font-size:14px;color:#1565c0;line-height:1.5;font-weight:bold;">'
         .     '&#128230; Что дальше?'
         .   '</p>'
         .   '<p style="margin:0;font-size:14px;color:#555;line-height:1.5;">'
         .     'Как только заказ будет упакован и передан в службу доставки, мы отправим вам письмо '
         .     'с <strong>фотографией товаров</strong> и <strong>трек-номером</strong> для отслеживания.'
         .   '</p>'
         . '</div>'

         ,
         $first_name, $order_number
      );
   } else {
      $subject = sprintf( '[Ru-Pills.com] Order #%s is being packed', $order_number );
      $heading = sprintf( 'Order #%s is being packed', $order_number );
      $message_body = sprintf(
         '<p style="font-size:15px;color:#333;line-height:1.6;margin:0 0 16px;">Hello, <strong>%s</strong>!</p>'

         . '<div style="background:#eaf7ec;border-left:4px solid #4caf50;padding:14px 18px;border-radius:4px;margin-bottom:20px;">'
         .   '<p style="margin:0;font-size:15px;color:#2e7d32;line-height:1.5;">'
         .     '&#10003; Your order <strong>#%s</strong> has left the pharmacy and has been handed over to the packing department.'
         .   '</p>'
         . '</div>'

         . '<div style="background:#fff3e0;border-left:4px solid #ff9800;padding:14px 18px;border-radius:4px;margin-bottom:20px;">'
         .   '<p style="margin:0 0 6px;font-size:14px;color:#e65100;line-height:1.5;font-weight:bold;">'
         .     '&#9888; Important notice'
         .   '</p>'
         .   '<p style="margin:0;font-size:14px;color:#555;line-height:1.5;">'
         .     'Your order is now being processed — <strong>it can no longer be cancelled, modified, or have its delivery address changed</strong>.'
         .   '</p>'
         . '</div>'

         . '<div style="background:#e3f2fd;border-left:4px solid #2196f3;padding:14px 18px;border-radius:4px;margin-bottom:20px;">'
         .   '<p style="margin:0 0 6px;font-size:14px;color:#1565c0;line-height:1.5;font-weight:bold;">'
         .     '&#128230; What happens next?'
         .   '</p>'
         .   '<p style="margin:0;font-size:14px;color:#555;line-height:1.5;">'
         .     'Once your order is packed and handed over to the shipping carrier, we will send you an email '
         .     'with a <strong>photo of the items</strong> and a <strong>tracking number</strong>.'
         .   '</p>'
         . '</div>'

         ,
         $first_name, $order_number
      );
   }

   $message = $mailer->wrap_message( $heading, $message_body );
   $mailer->send( $order->get_billing_email(), $subject, $message );
}
//-------------- email о смене статуса заказа ------------------ //

//----------------- максимальный вес заказа -------------------------------
 // Set a minimum weight requirement before checking out
 add_action( 'woocommerce_check_cart_items', 'spyr_set_weight_requirements' );
 function spyr_set_weight_requirements() {
   // Only run in the Cart or Checkout pages
   if( is_cart() || is_checkout() ) {
     global $woocommerce;
     // Set the minimum weight before checking out
     $minimum_weight = 0.9;
     // Get the Cart's content total weight
     $cart_contents_weight = WC()->cart->cart_contents_weight;
     // Compare values and add an error is Cart's total weight
       // happens to be less than the minimum required before checking out.
     // Will display a message along the lines of
     // A Minimum Weight of 25kg is required before checking out. (Cont. below)
     // Current cart weight: 12.5kg
     if( $cart_contents_weight > $minimum_weight  ) {
       // Display our error message
       wc_add_notice( sprintf('<strong>'.__('The maximum order weight of 1 kg has been exceeded. If you need more, please place multiple orders.').'</strong>',
         $minimum_weight,
         get_option( 'woocommerce_weight_unit' ),
         $cart_contents_weight,
         get_option( 'woocommerce_weight_unit' ),
         get_permalink( wc_get_page_id( 'shop' ) )
         ),
       'error'	);
     }
   }
 }
 //----------------- максимальный вес заказа -------------------------------

 //подсчет оригинальной цены в админке (править)
// Добавляем заголовки
function my_admin_order_item_headers($order) {
    echo '<th class="line_sku sortable" data-sort="your-sort-option">Original price</th>';
    echo '<th class="line_weight">Total Weight (g)</th>';
}
add_action( 'woocommerce_admin_order_item_headers', 'my_admin_order_item_headers', 10, 1 );

// Добавляем содержимое
function my_admin_order_item_values( $product, $item, $item_id ) {
    echo '<td class="sku">';
    $price = (($item->get_total() / $item->get_quantity()) * 55);
    
    $kof = 1.2;
    if ($price <= 20000) { $kof = 1.3; }
    if ($price <= 500)  { $kof = 1.4; }
    if ($price <= 200)  { $kof = 1.6; }
    if ($price <= 100)  { $kof = 2; }

    $price = round($price / $kof);
    echo '<b>' . $price . ' руб.</b>';
    echo '</td>';

    echo '<td class="weight">';
    if ($product && $product->has_weight()) {
        $weight_kg = (float) $product->get_weight();
        $quantity = (int) $item->get_quantity();
        $total_weight_g = round($weight_kg * 1000 * $quantity);

        // Оборачиваем в span с классом и data-атрибутом
        echo '<span class="copy-weight" data-weight="' . esc_attr($total_weight_g) . '" title="Click to copy weight">' . $total_weight_g . ' г</span>';
    } else {
        echo '—';
    }
    echo '</td>';
}
add_action( 'woocommerce_admin_order_item_values', 'my_admin_order_item_values', 10, 3 );

// Подключаем JS на всех страницах редактирования заказов (включая HPOS)
function admin_copy_weight_script() {
    $screen = get_current_screen();
    // Проверяем оба возможных ID экрана: старый и новый (HPOS)
    if ( $screen && ( $screen->id === 'shop_order' || $screen->id === 'woocommerce_page_wc-orders' ) ) {
        ?>
        <script>
        (function($) {
            'use strict';

            function initCopyWeight() {
                $(document).off('click', '.copy-weight').on('click', '.copy-weight', function(e) {
                    e.preventDefault();
                    const $el = $(this);
                    const weight = $el.data('weight');

                    if (!weight) return;

                    if (navigator.clipboard && window.isSecureContext) {
                        // Современный способ (HTTPS или localhost)
                        navigator.clipboard.writeText(weight.toString()).then(function() {
                            showFeedback($el, '✓ Скопировано!');
                        }).catch(function(err) {
                            console.error('Ошибка копирования:', err);
                        });
                    } else {
                        // Fallback для HTTP (не localhost)
                        const textArea = document.createElement('textarea');
                        textArea.value = weight;
                        textArea.style.position = 'fixed';
                        textArea.style.top = '-9999px';
                        document.body.appendChild(textArea);
                        textArea.select();
                        try {
                            const success = document.execCommand('copy');
                            if (success) {
                                showFeedback($el, '✓ Скопировано!');
                            } else {
                                showFeedback($el, 'Не удалось скопировать');
                            }
                        } catch (err) {
                            console.error('Fallback копирования не сработал:', err);
                        }
                        document.body.removeChild(textArea);
                    }
                });
            }

            function showFeedback($el, text) {
                const original = $el.html();
                $el.html(text).css('color', '#008000');
                setTimeout(function() {
                    $el.html(original).css('color', '');
                }, 1200);
            }

            // Запускаем при загрузке
            $(document).ready(initCopyWeight);

            // Также перезапускаем после AJAX (например, при добавлении товара в заказ)
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.url && settings.url.includes('admin-ajax.php') && 
                    (settings.data && (settings.data.includes('action=woocommerce_add_order_item') || 
                                      settings.data.includes('action=woocommerce_load_order_items')))) {
                    initCopyWeight();
                }
            });

        })(jQuery);
        </script>
        <?php
    }
}
add_action( 'admin_footer', 'admin_copy_weight_script' );
//-------------

//------------------ похожие товары из той же категории 
/**
* Only show products in the same sub categories in the related products area
*
* @param $terms - Terms currently being passed through
* @param $product_id - Product ID of related products request
* @return $terms/$subcategories - Terms to be included in related products query
*/
function blz_filter_related_products_subcats_only($terms, $product_id) {
  // Check to see if this product has only one category ticked
$prodterms = get_the_terms($product_id, 'product_cat');
if (is_array($prodterms) && count($prodterms) === 1) {
  return $terms;
}
  
  // Loop through the product categories and remove parent categories
$subcategories = array();
foreach ($prodterms as $k => $prodterm) {
  if ($prodterm->parent === 0) {
    unset($prodterms[$k]);
  } else {
    $subcategories[] = $prodterm->term_id;
  }
}
return $subcategories;
}
add_filter( 'woocommerce_get_related_product_cat_terms', 'blz_filter_related_products_subcats_only', 20, 2 );
//------------------ похожие товары из той же категории 

//------ добавление поля в поиск woocommerce
/*
function custom_search( $query ) {

  if( ! is_admin() && $query->is_main_query() ) {

      if ( $query->is_search() ) { 

          $meta_query = $query->get( 'meta_query' );

          $meta_query[] = array(
              'key'       => 'yikes_woo_products_tabs',
              'value'     => $query->query['s'],
              'compare'   => 'LIKE'  
          );

          $query->set( 'meta_query', $meta_query );

      }

  }

}

add_action( 'woocommerce_product_query' , 'custom_search' );
*/
//----------------

// отключение вариантов сортировки
function my_woocommerce_catalog_orderby( $orderby ) {
  unset($orderby["rating"]);
  unset($orderby["date"]);
  //unset($orderby["date"]);
  
  return $orderby;
}
add_filter( "woocommerce_catalog_orderby", "my_woocommerce_catalog_orderby", 20 );
//-----------
//----- сортировка в поиске по наличию--- !!!
/*
add_action( 'woocommerce_product_query', 'sort_by_stock_status_and_date', 999 );
function sort_by_stock_status_and_date( $query ) {
    if ( is_admin() ) return;

    $query->set( 'meta_key', '_stock_status' );
    $query->set( 'orderby', array( 'meta_value' => 'ASC' )  );
}*/
add_action( 'woocommerce_product_query', 'sort_by_stock_status_and_date', 999 );
function sort_by_stock_status_and_date( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;

    $query->set( 'meta_key', '_stock_status' ); 
    $query->set( 'orderby', array(
        'meta_value' => 'ASC' // Сортировка по наличию
        
    ));
}
//-------------------




//-------- текст на странице товара (SEO-оптимизированный: бренд + категория + доставка)
add_action( 'woocommerce_after_single_product', 'wpbl_example_hook', 20 );

function wpbl_example_hook() {
    $product = wc_get_product( get_the_ID() );
    if ( ! $product ) return;

    $title = $product->get_name();
    $lang  = apply_filters( 'wpml_current_language', 'en' );
    $brand = rupills_get_product_brand( $product );

    $cats     = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
    $category = ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ? $cats[0] : '';

    if ( $lang === 'ru' ) {
        $text = '<strong>Купить ' . esc_html( $title ) . '</strong>';
        if ( $brand ) $text .= ' от ' . esc_html( $brand );
        $text .= ' в интернет-аптеке Ru-Pills.com.';
        if ( $category ) $text .= ' ' . esc_html( $category ) . ' —';
        $text .= ' доставка в США, Великобританию, Европу и более 120 стран мира.';
    } else {
        $text = '<strong>Buy ' . esc_html( $title ) . '</strong>';
        if ( $brand ) $text .= ' by ' . esc_html( $brand );
        $text .= ' at Ru-Pills.com online pharmacy.';
        if ( $category ) $text .= ' ' . esc_html( $category ) . ' —';
        $text .= ' delivery to USA, UK, Europe and over 120 other countries.';
    }

    echo '<p style="text-align:center;margin:0 5px;">' . $text . '</p>';
}

// ------------

// удаляет нет в наличии из похожие товары
add_filter( 'woocommerce_related_products', 'exclude_oos_related_products', 10, 3 );

function exclude_oos_related_products( $related_posts, $product_id, $args ){
    $out_of_stock_product_ids = (array) wc_get_products( array(
          'status'       => 'publish',
          'limit'        => -1,
          'stock_status' => 'outofstock',
          'return'       => 'ids',
      ) );

    $exclude_ids = $out_of_stock_product_ids;

    return array_diff( $related_posts, $exclude_ids );
}
///---------------





//-------- изминение текста --------------
add_filter('gettext', 'translate_reply');
add_filter('ngettext', 'translate_reply');

function translate_reply($translated) {
$translated = str_ireplace('Billing', 'Shipping', $translated);
$translated = str_ireplace('Платёжный адрес', 'Адрес доставки', $translated);
$translated = str_ireplace('Addresses', 'Address', $translated);
$translated = str_ireplace('Адреса', 'Адрес', $translated);
$translated = str_ireplace('Username or email address', 'Email address', $translated);
$translated = str_ireplace('Имя пользователя или email', 'Email', $translated);
$translated = str_ireplace('Email или имя пользователя', 'Email', $translated);
$translated = str_ireplace('Имя пользователя или электронная почта', 'Email', $translated);
$translated = str_ireplace('Username or email', 'Email', $translated);

//$product_title_my = get_the_title( $comment->comment_post_ID );
//$product_title_my = preg_split("/[\s,]+/", $product_title_my);
$translated = str_ireplace('You may also like', 'Other forms', $translated);
$translated = str_ireplace('Вам также будет интересно', 'Другие формы', $translated);

$translated = str_ireplace('Filter', 'Subcategories', $translated);
$translated = str_ireplace('Фильтровать', 'Подкатегории', $translated);

return $translated;
}

//-------- изминение текста --------------

//---- уведомление об обновлении
/*
add_action( 'woocommerce_login_form', 'add_custom_block_to_login_footer' );

function add_custom_block_to_login_footer() {
	?>
	<strong style="color:red">ATTENTION!!! We have completed a global update. [25.12.2022] <a href="/update">Click for details.</a></strong>
	<?php
}*/
//-------------

///------- отключаем лишние поля чекоута при виртуальном товаре (депозит)
add_filter( 'woocommerce_checkout_fields' , 'truemisha_checkout_for_virtual_products', 25 );
 function truemisha_checkout_for_virtual_products( $fields ) {
 $is_only_virtual = true;
 foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		// если хотя бы один товар не виртуальный, то ничего делать не будем
		if ( ! $cart_item['data']->is_virtual() ) {
			$is_only_virtual = false;
			break;
		}
	}
 
if( $is_only_virtual ) {
    add_filter( 'woocommerce_enable_order_notes_field', '__return_false', 9999 );
		unset( $fields[ 'billing' ][ 'billing_company' ] );
		unset( $fields[ 'billing' ][ 'billing_address_1' ] );
		unset( $fields[ 'billing' ][ 'billing_address_2' ] );
		unset( $fields[ 'billing' ][ 'billing_city' ] );
		unset( $fields[ 'billing' ][ 'billing_postcode' ] );
		unset( $fields[ 'billing' ][ 'billing_country' ] );
		unset( $fields[ 'billing' ][ 'billing_state' ] );
		unset( $fields[ 'billing' ][ 'billing_phone' ] );
	}
	return $fields;
}
//--------

//----- устонавливаем авторизацию на год

add_filter ( 'auth_cookie_expiration', 'extend_login_session' );
function extend_login_session( $expire ) {
    return YEAR_IN_SECONDS;
}
//---------

//------ удаляет shop из хлебных крошек yoast

add_filter( 'wpseo_breadcrumb_links' ,'wpseo_remove_breadcrumb_link', 10 );

function wpseo_remove_breadcrumb_link( $links ){
    // Remove all breadcrumbs that have the text: Shop.
    $new_links = array_filter( $links, function ( $link ) { return !in_array($link['text'], ['Shop', 'Магазин']); } );
    // Reset array keys.
    return array_values( $new_links );
}
//-------    

//------- добавляет бренд в Product schema (формат @type: Brand для Google Rich Snippets)
add_filter( 'wpseo_schema_product', 'custom_set_extra_schema' );
function custom_set_extra_schema( $data ) {
    global $product;
    $brand_name = rupills_get_product_brand( $product );

    if ( ! empty( $brand_name ) ) {
        $data['brand'] = array(
            '@type' => 'Brand',
            'name'  => $brand_name,
        );
    }

    return $data;
}
//-----------
 


//woocommerce_before_checkout_form




//----- текст кнопки когда нет в наличии
/* OUT OF STOCK WORDING UPDATES */
add_filter( 'woocommerce_product_add_to_cart_text', 'bbloomer_archive_custom_cart_button_text' );
  
function bbloomer_archive_custom_cart_button_text( $text ) {
   global $product;       
   if ( $product && ! $product->is_in_stock() ) {       
    $button_text = __('Get Notified', 'woocommerce');    
      return $button_text;
   } 
   return $text;
}
//------------



//------- отключает pingback
function wpschool_remove_pingback_header( $headers ) {
  unset( $headers['X-Pingback'] );
  return $headers;
}

function wpschool_remove_x_pingback_headers( $headers ) {
  if ( function_exists( 'header_remove' ) ) {
      header_remove( 'X-Pingback' );
      header_remove( 'Server' );
  }
}

function wpschool_block_xmlrpc_attacks( $methods ) {
  unset( $methods['pingback.ping'] );
  unset( $methods['pingback.extensions.getPingbacks'] );
  return $methods;
}

add_filter( 'wp_headers', 'wpschool_remove_pingback_header' );
add_filter( 'template_redirect', 'wpschool_remove_x_pingback_headers' );
add_filter( 'xmlrpc_methods', 'wpschool_block_xmlrpc_attacks' );
add_filter( 'xmlrpc_enabled','__return_false' );
//--------



/*
function custom_login_redirect() {
  $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'].'?&rand='.rand(1,1000).'';
//echo $url;

  return $url;
  
  }
  
  add_filter('woocommerce_login_redirect', 'custom_login_redirect');

  */
//--- сортировка поиска relevanssi
  //add_filter( 'relevanssi_orderby', function( $orderby ) { return array( 'post_title' => 'asc', 'relevance' => 'desc' ); } );
  //add_filter( 'relevanssi_orderby', function( $orderby ) { return 'post_title'; } );
  //add_filter( 'relevanssi_order', function( $order ) { return 'asc'; } );
//--- сортировка поиска relevanssi  



//------- увеличение таймаута для блокономикс, чтобы не было ошибки Curl 28
add_filter('http_request_timeout', function ($timeout) {
  $newtimeout = 10;
  return $newtimeout;
});
//--------------

//-------- скрытие веса товара
add_filter('woocommerce_product_get_weight', 'hide_weight_on_product_page', 10, 2);
add_filter('woocommerce_product_variation_get_weight', 'hide_weight_on_product_page', 10, 2);

function hide_weight_on_product_page($weight, $product) {
    if (is_product()) { // Проверяем, что это страница товара
        return ''; // Убираем отображение
    }
    return $weight; // Оставляем вес для расчетов доставки
}
//-------- скрытие веса товара

// --- временное решение для кодировки русского текста в собственных вкладках
//add_filter('yikes_woocommerce_custom_repeatable_product_tabs_content', function ($content) {
//  return mb_convert_encoding($content, 'ISO-8859-1', 'UTF-8');
//});

//---------- показываем строку поиска в результатах поиска ------------
function custom_search_form_before_results() {
  if (is_search() && get_query_var('post_type') === 'product') {
      echo '<div class="custom-search-form">';
      get_search_form();
      echo '</div>';
  }
}
add_action('woocommerce_before_main_content', 'custom_search_form_before_results', 15);

function modify_search_form($form) {
  if (is_search() && get_query_var('post_type') === 'product') {
      $form = '<form role="search" method="get" class="searchform" action="' . home_url( '/' ) . '">
	<div class="flex-row relative">
						<div class="flex-col flex-grow">
			<label class="screen-reader-text" for="woocommerce-product-search-field-1">Search for:</label>
			<input type="search" id="woocommerce-product-search-field-1" class="search-field mb-0" placeholder="Search&hellip;" value="' . esc_attr( get_search_query() ) . '" name="s">
			<input type="hidden" name="post_type" value="product">
							<input type="hidden" name="lang" value="en">
					</div>
		<div class="flex-col">
			<button type="submit" value="Search" class="ux-search-submit submit-button secondary button wp-element-button icon mb-0" aria-label="Submit">
				<i class="icon-search"></i>			</button>
		</div>
	</div>
	<div class="live-search-results text-left z-top"></div>
</form>';
  }
  return $form;
}
add_filter('get_search_form', 'modify_search_form');
//---------- показываем строку поиска в результатах поиска ------------


// Abandoned cart recovery is now handled by mu-plugins/rupills-abandoned-carts.php

//---- вес товара в админке 

// Получаем название на английском через WPML
function get_product_name_in_language( $product_id, $lang_code = 'en' ) {
    if ( ! function_exists( 'icl_object_id' ) || ! defined( 'ICL_LANGUAGE_CODE' ) ) {
        return get_the_title( $product_id );
    }

    global $sitepress;
    if ( ! $sitepress ) {
        return get_the_title( $product_id );
    }

    $current_lang = $sitepress->get_current_language();
    $sitepress->switch_lang( $lang_code );

    $translated_id = icl_object_id( $product_id, 'product', true, $lang_code );
    $name = $translated_id && get_post_status( $translated_id ) === 'publish' 
        ? get_the_title( $translated_id ) 
        : get_the_title( $product_id );

    $sitepress->switch_lang( $current_lang );
    return $name;
}

// Добавляем иконку сразу после ссылки с названием
function add_english_name_copy_icon( $product, $item, $item_id ) {
    if ( ! $product ) return;

    $english_name = get_product_name_in_language( $product->get_id(), 'en' );
    if ( ! $english_name ) return;

    $escaped_name = esc_js( $english_name );
    ?>
    <script>
    (function($) {
        'use strict';

        function insertCopyIcon() {
            const row = document.querySelector('tr.item[data-order_item_id="<?php echo esc_js( $item_id ); ?>"]');
            if (!row) return;

            const nameCell = row.querySelector('td.name');
            if (!nameCell || nameCell.querySelector('.wc-copy-en-icon')) return;

            const nameLink = nameCell.querySelector('a.wc-order-item-name');
            if (!nameLink) return;

            const icon = document.createElement('span');
            icon.className = 'wc-copy-en-icon';
            icon.title = 'Copy English name';
            icon.innerHTML = '📋';
            icon.style.cursor = 'pointer';
            icon.style.color = '#666';
            icon.style.marginLeft = '6px';
            icon.style.verticalAlign = 'middle';
            icon.dataset.englishName = '<?php echo $escaped_name; ?>';

            // 🔑 Вставляем СРАЗУ ПОСЛЕ ссылки <a>
            nameLink.after(icon);
        }

        // Обработчик копирования (один раз)
        if (!window.wcCopyEnHandler) {
            window.wcCopyEnHandler = true;
            $(document).on('click', '.wc-copy-en-icon', function() {
                const name = this.dataset.englishName;
                if (!name) return;

                const $el = $(this);
                const original = $el.html();

                const copyToClipboard = (text) => {
                    if (navigator.clipboard && window.isSecureContext) {
                        return navigator.clipboard.writeText(text);
                    } else {
                        const textarea = document.createElement('textarea');
                        textarea.value = text;
                        textarea.style.position = 'fixed';
                        textarea.style.top = '-9999px';
                        document.body.appendChild(textarea);
                        textarea.select();
                        const result = document.execCommand('copy');
                        document.body.removeChild(textarea);
                        return result ? Promise.resolve() : Promise.reject();
                    }
                };

                copyToClipboard(name).then(() => {
                    $el.html('✓').css('color', '#008000');
                    setTimeout(() => $el.html(original).css('color', '#666'), 1000);
                }).catch(() => {
                    $el.html('✗').css('color', 'red');
                    setTimeout(() => $el.html(original).css('color', '#666'), 1000);
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', insertCopyIcon);
        } else {
            insertCopyIcon();
        }

    })(jQuery);
    </script>
    <?php
}
add_action( 'woocommerce_admin_order_item_values', 'add_english_name_copy_icon', 20, 3 );
//---- вес товара в админке 

// удаление фото вместе с удалением товаров
add_action('before_delete_post', function($post_id) {
    $product = wc_get_product($post_id);
    if (!$product) return;
    
    // Удаляем главное изображение
    $featured_image_id = $product->get_image_id();
    if ($featured_image_id) {
        wp_delete_attachment($featured_image_id, true);
    }
    
    // Удаляем галерею
    $gallery_image_ids = $product->get_gallery_image_ids();
    if ($gallery_image_ids) {
        foreach ($gallery_image_ids as $image_id) {
            wp_delete_attachment($image_id, true);
        }
    }
});
// удаление фото вместе с удалением товаров

// ============ Google Analytics 4 (GA4) ============

add_action('wp_head', 'rupills_ga4_head', 1);
function rupills_ga4_head() {
    $ga_id = 'G-R8LTVK503R';
    ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ga_id; ?>"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?php echo $ga_id; ?>', {
        'send_page_view': true,
        'cookie_flags': 'SameSite=None;Secure'
    });
    </script>
    <?php
}

// ============ GA4 E-commerce: view_item (product page) ============

add_action('wp_footer', 'rupills_ga4_view_item', 10);
function rupills_ga4_view_item() {
    if ( ! is_product() ) return;
    $product = wc_get_product( get_the_ID() );
    if ( ! $product ) return;

    $cats   = wp_get_post_terms( $product->get_id(), 'product_cat', ['fields' => 'names'] );
    $cat    = ( ! is_wp_error($cats) && ! empty($cats) ) ? $cats[0] : '';
    $brand  = rupills_get_product_brand( $product );
    $price  = $product->get_price() ? (float) $product->get_price() : 0;
    ?>
    <script>
    gtag('event', 'view_item', {
        currency: '<?php echo get_woocommerce_currency(); ?>',
        value: <?php echo $price; ?>,
        items: [{
            item_id: '<?php echo esc_js( $product->get_sku() ?: $product->get_id() ); ?>',
            item_name: <?php echo wp_json_encode( $product->get_name() ); ?>,
            item_brand: <?php echo wp_json_encode( $brand ); ?>,
            item_category: <?php echo wp_json_encode( $cat ); ?>,
            price: <?php echo $price; ?>,
            quantity: 1
        }]
    });
    </script>
    <?php
}

// ============ GA4 E-commerce: view_item_list (category / shop) ============

add_action('wp_footer', 'rupills_ga4_view_item_list', 10);
function rupills_ga4_view_item_list() {
    if ( ! is_shop() && ! is_product_category() && ! is_product_tag() && ! is_search() ) return;
    global $wp_query;
    if ( empty($wp_query->posts) ) return;

    $list_name = 'Shop';
    if ( is_product_category() ) {
        $list_name = single_term_title('', false);
    } elseif ( is_search() ) {
        $list_name = 'Search Results';
    }

    $items = [];
    $pos   = 1;
    foreach ( $wp_query->posts as $post_obj ) {
        $p = wc_get_product($post_obj->ID);
        if ( ! $p ) continue;
        $cats = wp_get_post_terms($p->get_id(), 'product_cat', ['fields' => 'names']);
        $items[] = [
            'item_id'       => $p->get_sku() ?: (string) $p->get_id(),
            'item_name'     => $p->get_name(),
            'item_brand'    => rupills_get_product_brand($p),
            'item_category' => ( ! is_wp_error($cats) && ! empty($cats) ) ? $cats[0] : '',
            'price'         => (float) $p->get_price(),
            'index'         => $pos++,
        ];
        if ($pos > 20) break;
    }
    ?>
    <script>
    gtag('event', 'view_item_list', {
        item_list_name: <?php echo wp_json_encode($list_name); ?>,
        items: <?php echo wp_json_encode($items, JSON_UNESCAPED_UNICODE); ?>
    });
    </script>
    <?php
}

// ============ GA4 E-commerce: add_to_cart (AJAX + non-AJAX) ============

add_action('wp_footer', 'rupills_ga4_add_to_cart_js', 20);
function rupills_ga4_add_to_cart_js() {
    if ( is_admin() ) return;
    ?>
    <script data-no-optimize="1">
    (function(){
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.add_to_cart_button, .single_add_to_cart_button');
            if (!btn) return;
            var wrap = btn.closest('[data-product_id]') || btn.closest('form.cart');
            if (!wrap) return;

            var id    = wrap.getAttribute('data-product_id') || '';
            var name  = wrap.getAttribute('data-product_name') || '';
            var price = wrap.getAttribute('data-product_price') || '0';

            if (!id || !name) {
                var hiddenId    = wrap.querySelector('input[name="ga4_product_id"]');
                var hiddenName  = wrap.querySelector('input[name="ga4_product_name"]');
                var hiddenPrice = wrap.querySelector('input[name="ga4_product_price"]');
                if (hiddenId)    id    = hiddenId.value;
                if (hiddenName)  name  = hiddenName.value;
                if (hiddenPrice) price = hiddenPrice.value;
            }
            if (!name) name = (document.querySelector('h1.product-title, h1.product_title, .product_title') || {}).textContent || '';
            name = name.trim();

            var qty = 1;
            var qtyInput = wrap.querySelector('input[name="quantity"]');
            if (qtyInput) qty = parseInt(qtyInput.value) || 1;

            if (typeof gtag === 'function') {
                gtag('event', 'add_to_cart', {
                    currency: '<?php echo get_woocommerce_currency(); ?>',
                    value: parseFloat(price) * qty,
                    items: [{
                        item_id: id,
                        item_name: name,
                        price: parseFloat(price),
                        quantity: qty
                    }]
                });
            }
        });
    })();
    </script>
    <?php
}

// ============ GA4 E-commerce: begin_checkout ============

add_action('wp_footer', 'rupills_ga4_begin_checkout', 10);
function rupills_ga4_begin_checkout() {
    if ( ! is_checkout() || is_order_received_page() ) return;
    $cart = WC()->cart;
    if ( ! $cart || $cart->is_empty() ) return;

    $items = [];
    $pos   = 1;
    foreach ( $cart->get_cart() as $item ) {
        $p = $item['data'];
        $cats = wp_get_post_terms($p->get_id(), 'product_cat', ['fields' => 'names']);
        $items[] = [
            'item_id'       => $p->get_sku() ?: (string) $p->get_id(),
            'item_name'     => $p->get_name(),
            'item_brand'    => rupills_get_product_brand($p),
            'item_category' => ( ! is_wp_error($cats) && ! empty($cats) ) ? $cats[0] : '',
            'price'         => (float) $p->get_price(),
            'quantity'      => (int) $item['quantity'],
            'index'         => $pos++,
        ];
    }
    ?>
    <script>
    gtag('event', 'begin_checkout', {
        currency: '<?php echo get_woocommerce_currency(); ?>',
        value: <?php echo (float) $cart->get_total('edit'); ?>,
        items: <?php echo wp_json_encode($items, JSON_UNESCAPED_UNICODE); ?>
    });
    </script>
    <?php
}

// ============ GA4 E-commerce: purchase (thank-you page) ============

add_action('wp_footer', 'rupills_ga4_purchase', 10);
function rupills_ga4_purchase() {
    if ( ! is_order_received_page() ) return;
    global $wp;
    $order_id = isset($wp->query_vars['order-received']) ? absint($wp->query_vars['order-received']) : 0;
    if ( ! $order_id ) return;

    $order = wc_get_order($order_id);
    if ( ! $order ) return;

    if ( $order->get_meta('_ga4_purchase_tracked') === 'yes' ) return;

    $items = [];
    $pos   = 1;
    foreach ( $order->get_items() as $item ) {
        $p = $item->get_product();
        if ( ! $p ) continue;
        $cats = wp_get_post_terms($p->get_id(), 'product_cat', ['fields' => 'names']);
        $items[] = [
            'item_id'       => $p->get_sku() ?: (string) $p->get_id(),
            'item_name'     => $item->get_name(),
            'item_brand'    => rupills_get_product_brand($p),
            'item_category' => ( ! is_wp_error($cats) && ! empty($cats) ) ? $cats[0] : '',
            'price'         => (float) $order->get_item_total($item),
            'quantity'      => (int) $item->get_quantity(),
            'index'         => $pos++,
        ];
    }

    $coupon_list = $order->get_coupon_codes();
    ?>
    <script>
    gtag('event', 'purchase', {
        transaction_id: '<?php echo esc_js($order->get_order_number()); ?>',
        value: <?php echo (float) $order->get_total(); ?>,
        tax: <?php echo (float) $order->get_total_tax(); ?>,
        shipping: <?php echo (float) $order->get_shipping_total(); ?>,
        currency: '<?php echo esc_js($order->get_currency()); ?>',
        coupon: <?php echo wp_json_encode( implode(',', $coupon_list) ); ?>,
        items: <?php echo wp_json_encode($items, JSON_UNESCAPED_UNICODE); ?>
    });
    </script>
    <?php

    $order->update_meta_data('_ga4_purchase_tracked', 'yes');
    $order->update_meta_data('_ga4_tracked_via', 'client');
    $order->save();
}

// ============ GA4 E-commerce: view_cart ============

add_action('wp_footer', 'rupills_ga4_view_cart', 10);
function rupills_ga4_view_cart() {
    if ( ! is_cart() ) return;
    $cart = WC()->cart;
    if ( ! $cart || $cart->is_empty() ) return;

    $items = [];
    $pos   = 1;
    foreach ( $cart->get_cart() as $item ) {
        $p = $item['data'];
        $cats = wp_get_post_terms($p->get_id(), 'product_cat', ['fields' => 'names']);
        $items[] = [
            'item_id'       => $p->get_sku() ?: (string) $p->get_id(),
            'item_name'     => $p->get_name(),
            'item_brand'    => rupills_get_product_brand($p),
            'item_category' => ( ! is_wp_error($cats) && ! empty($cats) ) ? $cats[0] : '',
            'price'         => (float) $p->get_price(),
            'quantity'      => (int) $item['quantity'],
            'index'         => $pos++,
        ];
    }
    ?>
    <script>
    gtag('event', 'view_cart', {
        currency: '<?php echo get_woocommerce_currency(); ?>',
        value: <?php echo (float) $cart->get_total('edit'); ?>,
        items: <?php echo wp_json_encode($items, JSON_UNESCAPED_UNICODE); ?>
    });
    </script>
    <?php
}

// GA4: data-attributes на кнопки «Добавить в корзину» в каталоге
add_filter('woocommerce_loop_add_to_cart_args', 'rupills_ga4_cart_button_attrs', 10, 2);
function rupills_ga4_cart_button_attrs($args, $product) {
    $args['attributes']['data-product_name']  = $product->get_name();
    $args['attributes']['data-product_price'] = $product->get_price();
    return $args;
}

// GA4: data-attributes для формы на странице товара
add_action('woocommerce_before_add_to_cart_button', 'rupills_ga4_single_product_data');
function rupills_ga4_single_product_data() {
    global $product;
    if ( ! $product ) return;
    printf(
        '<input type="hidden" name="ga4_product_id" value="%s">'
        . '<input type="hidden" name="ga4_product_name" value="%s">'
        . '<input type="hidden" name="ga4_product_price" value="%s">',
        esc_attr( $product->get_sku() ?: $product->get_id() ),
        esc_attr( $product->get_name() ),
        esc_attr( $product->get_price() )
    );
}

// ============ GA4 Server-side: сохраняем client_id при оформлении заказа ============

add_action('woocommerce_checkout_update_order_meta', 'rupills_save_ga_client_id', 10, 1);
function rupills_save_ga_client_id($order_id) {
    if (empty($_COOKIE['_ga'])) return;
    // Cookie _ga имеет формат GA1.1.XXXXXXXXX.YYYYYYYYY — нужны последние 2 части
    $parts = explode('.', $_COOKIE['_ga']);
    $client_id = (count($parts) >= 4)
        ? $parts[2] . '.' . $parts[3]
        : $_COOKIE['_ga'];
    update_post_meta($order_id, '_ga4_client_id', sanitize_text_field($client_id));
}

// ============ GA4 Server-side: purchase через Measurement Protocol ============

add_action('woocommerce_order_status_processing', 'rupills_ga4_server_purchase', 20, 1);
add_action('woocommerce_payment_complete', 'rupills_ga4_server_purchase', 20, 1);
function rupills_ga4_server_purchase($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    if ($order->get_meta('_ga4_purchase_tracked') === 'yes') return;

    $measurement_id = 'G-R8LTVK503R';
    $api_secret     = 'hHU8JwoxTmqqtzxSLq451w';

    $client_id = get_post_meta($order_id, '_ga4_client_id', true);
    if (empty($client_id)) {
        $client_id = 'server.' . $order_id;
    }

    $items = [];
    foreach ($order->get_items() as $item) {
        $p = $item->get_product();
        $cats = $p ? wp_get_post_terms($p->get_id(), 'product_cat', ['fields' => 'names']) : [];
        $items[] = [
            'item_id'       => $p ? ($p->get_sku() ?: (string) $p->get_id()) : '',
            'item_name'     => $item->get_name(),
            'item_brand'    => $p ? rupills_get_product_brand($p) : '',
            'item_category' => (!is_wp_error($cats) && !empty($cats)) ? $cats[0] : '',
            'price'         => (float) $order->get_item_total($item),
            'quantity'      => (int) $item->get_quantity(),
        ];
    }

    $coupon_list = $order->get_coupon_codes();

    $payload = [
        'client_id' => $client_id,
        'events' => [[
            'name'   => 'purchase',
            'params' => [
                'transaction_id' => (string) $order->get_order_number(),
                'value'          => (float) $order->get_total(),
                'currency'       => $order->get_currency(),
                'shipping'       => (float) $order->get_shipping_total(),
                'tax'            => (float) $order->get_total_tax(),
                'coupon'         => implode(',', $coupon_list),
                'items'          => $items,
            ],
        ]],
    ];

    $response = wp_remote_post(
        "https://www.google-analytics.com/mp/collect?measurement_id={$measurement_id}&api_secret={$api_secret}",
        [
            'body'    => wp_json_encode($payload),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 5,
        ]
    );

    $success = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 204;

    $order->update_meta_data('_ga4_purchase_tracked', 'yes');
    $order->update_meta_data('_ga4_tracked_via', $success ? 'server' : 'server_error');
    $order->save();

    if ($success) {
        $order->add_order_note('GA4: purchase event sent via Measurement Protocol (server-side).');
    }
}

// ============ End GA4 E-commerce ============

// ============ PWA Support + Install Tracking via gtag (GA4) ============

add_action('wp_head', 'rupills_pwa_meta', 2);
function rupills_pwa_meta() {
    ?>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Ru-Pills">
    <link rel="apple-touch-icon" href="/icon_x192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icon_x192.png">
    <?php
}

add_action('wp_footer', 'rupills_pwa_sw_and_tracking', 999);
function rupills_pwa_sw_and_tracking() {
    ?>
    <script data-no-optimize="1">
    (function() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then(function(r) { console.log('PWA: SW registered', r.scope); })
                    .catch(function(e) { console.log('PWA: SW error', e); });
            });
        }

        var isPWA = window.matchMedia('(display-mode: standalone)').matches
                 || window.navigator.standalone === true;
        if (isPWA) {
            document.cookie = 'is_pwa=1; path=/; max-age=31536000; SameSite=Lax';
            if (typeof gtag === 'function') {
                gtag('event', 'pwa_launch', { pwa_action: 'launched' });
            }
        } else {
            document.cookie = 'is_pwa=; path=/; max-age=0';
        }

        window.addEventListener('beforeinstallprompt', function(e) {
            if (typeof gtag === 'function') {
                gtag('event', 'pwa_install_prompt', { pwa_action: 'prompt_shown' });
            }
        });

        window.addEventListener('appinstalled', function() {
            if (typeof gtag === 'function') {
                gtag('event', 'pwa_installed', { pwa_action: 'installed' });
            }
            console.log('PWA: App installed');
        });

        if (isPWA) {
            document.addEventListener('click', function(e) {
                var link = e.target.closest('a');
                if (!link) return;
                var href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript')) return;
                try {
                    var url = new URL(href, window.location.origin);
                    var isExternal = url.hostname !== window.location.hostname;
                    if (isExternal) {
                        e.preventDefault();
                        window.open(url.href, '_blank');
                    }
                } catch(err) {}
            }, true);
        }
    })();
    </script>
    <script data-no-optimize="1" async src="/pwa-install-prompt.js"></script>
    <?php
}

// 3. Shortcode [pwa_install_guide] — auto-opens modal + persuasion content below
add_shortcode('pwa_install_guide', 'rupills_pwa_install_guide');
function rupills_pwa_install_guide() {
    ob_start();
    ?>
    <style>
    .pwa-page{max-width:520px;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;padding:1rem 0 2rem}
    .pwa-page .hero{text-align:center;padding:2rem 1rem;background:linear-gradient(135deg,#f0f9ff 0%,#e8f4fd 100%);border-radius:20px;margin-bottom:2rem}
    .pwa-page .hero-icon{width:80px;height:80px;border-radius:18px;box-shadow:0 4px 16px rgba(41,171,226,.25);margin-bottom:1rem}
    .pwa-page .hero h2{font-size:1.4rem;color:#1a1a1a;margin:0 0 .5rem;font-weight:700}
    .pwa-page .hero p{color:#666;font-size:.95rem;margin:0 0 1.2rem;line-height:1.5}
    .pwa-page .install-btn{display:inline-block;padding:14px 40px;background:#29ABE2;color:#fff;border:none;border-radius:12px;font-size:1.05rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;box-shadow:0 4px 14px rgba(41,171,226,.3)}
    .pwa-page .install-btn:hover{background:#1d8bbf;transform:translateY(-1px);box-shadow:0 6px 20px rgba(41,171,226,.35)}
    .pwa-page .install-btn:active{transform:scale(.98)}
    .pwa-page .perks{margin-bottom:2rem}
    .pwa-page .perks h3{font-size:1.15rem;color:#1a1a1a;margin:0 0 1rem;text-align:center}
    .pwa-page .perk{display:flex;align-items:flex-start;gap:14px;padding:14px 16px;background:#fff;border-radius:14px;margin-bottom:10px;box-shadow:0 1px 8px rgba(0,0,0,.04);border:1px solid #f0f0f0}
    .pwa-page .perk-icon{font-size:1.6rem;flex-shrink:0;margin-top:2px}
    .pwa-page .perk-text{font-size:.93rem;color:#444;line-height:1.5}
    .pwa-page .perk-text strong{color:#1a1a1a}
    .pwa-page .miss{text-align:center;background:#fff8f0;border-radius:16px;padding:1.5rem 1.2rem;margin-bottom:1.5rem;border:1px solid #ffe8cc}
    .pwa-page .miss-icon{font-size:2rem;margin-bottom:.5rem}
    .pwa-page .miss p{color:#8a6d3b;font-size:.95rem;line-height:1.6;margin:0 0 1rem}
    .pwa-page .cta-bottom{text-align:center}
    </style>

    <div class="pwa-page">

        <div class="hero">
            <img src="/icon_x512.png" alt="Ru-Pills" class="hero-icon">
            <h2>Install Ru-Pills App</h2>
            <p>Get instant access to our pharmacy right from your home screen</p>
            <button class="install-btn" onclick="window.location.hash='wepp-install-modal'">Install App</button>
        </div>

        <div class="perks">
            <h3>Why install the app?</h3>
            <div class="perk">
                <span class="perk-icon">💊</span>
                <div class="perk-text"><strong>Hidden products</strong> — the app has exclusive items that are not available on the website due to copyright holders</div>
            </div>
            <div class="perk">
                <span class="perk-icon">🏷️</span>
                <div class="perk-text"><strong>App-only discounts</strong> — special coupons and promotions available exclusively in the app</div>
            </div>
            <div class="perk">
                <span class="perk-icon">⚡</span>
                <div class="perk-text"><strong>One-tap access</strong> — open the store instantly from your home screen, no browser needed</div>
            </div>
            <div class="perk">
                <span class="perk-icon">🔔</span>
                <div class="perk-text"><strong>Stay updated</strong> — be the first to know about new arrivals and sales</div>
            </div>
        </div>

        <div class="miss">
            <div class="miss-icon">🤔</div>
            <p>Changed your mind? You're missing out on <strong>hidden products</strong> and <strong>exclusive discounts</strong> that are only available in the app!</p>
            <button class="install-btn" onclick="window.location.hash='wepp-install-modal'">Install Now</button>
        </div>

    </div>

    <script data-no-optimize="1">
    (function(){
        if (!window.location.hash || window.location.hash.indexOf('wepp-install-modal') === -1) {
            window.location.hash = 'wepp-install-modal';
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}
// 4. Helper: detect PWA request (cookie or utm_source)
function is_pwa_request() {
    return ! empty( $_COOKIE['is_pwa'] )
        || ( isset( $_GET['utm_source'] ) && $_GET['utm_source'] === 'pwa' );
}

// 5. Metabox "App Only" on product edit page
add_action( 'add_meta_boxes', 'rupills_app_only_metabox' );
function rupills_app_only_metabox() {
    add_meta_box(
        'rupills_app_only_box',
        '📱 Приложение (PWA)',
        'rupills_render_app_only_box',
        'product',
        'side',
        'high'
    );
}

function rupills_render_app_only_box( $post ) {
    $value = get_post_meta( $post->ID, '_app_only', true );
    wp_nonce_field( 'rupills_app_only_nonce', 'rupills_app_only_nonce_field' );
    echo '<label style="font-size:13px;"><input type="checkbox" name="_app_only" value="yes" ' . checked( $value, 'yes', false ) . '> Только в приложении</label>';
    echo '<p class="description" style="margin-top:8px;">Товар будет скрыт на сайте и доступен только в PWA-приложении.</p>';
}

add_action( 'save_post_product', 'rupills_save_app_only_meta' );
function rupills_save_app_only_meta( $post_id ) {
    if ( ! isset( $_POST['rupills_app_only_nonce_field'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['rupills_app_only_nonce_field'], 'rupills_app_only_nonce' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $val = isset( $_POST['_app_only'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_app_only', $val );
    
    // Clear cache when product is updated
    delete_transient( 'rupills_app_only_ids_v1' );
}

// 6. Hide app-only products from catalog, search, categories (non-PWA visitors)
// OPTIMIZED: Using cached ID list instead of slow meta_query JOIN
function rupills_get_app_only_product_ids() {
    $cache_key = 'rupills_app_only_ids_v1';
    $cached = get_transient( $cache_key );
    
    if ( false !== $cached ) {
        return $cached;
    }
    
    global $wpdb;
    $ids = $wpdb->get_col( "
        SELECT post_id 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_app_only' 
        AND meta_value = 'yes'
    " );
    
    $ids = array_map( 'intval', $ids );
    set_transient( $cache_key, $ids, HOUR_IN_SECONDS );
    
    return $ids;
}

add_action( 'woocommerce_product_query', 'rupills_filter_app_only_products' );
function rupills_filter_app_only_products( $query ) {
    if ( is_admin() || is_pwa_request() ) return;

    $app_only_ids = rupills_get_app_only_product_ids();
    
    if ( ! empty( $app_only_ids ) ) {
        $exclude = $query->get( 'post__not_in' );
        if ( ! is_array( $exclude ) ) $exclude = [];
        $exclude = array_merge( $exclude, $app_only_ids );
        $query->set( 'post__not_in', $exclude );
    }
}

// 7. App-only product pages: disable LiteSpeed Cache + block non-PWA access → 404
add_action( 'template_redirect', 'rupills_block_app_only_direct_access' );
function rupills_block_app_only_direct_access() {
    if ( ! is_product() ) return;

    $product_id = get_the_ID();
    if ( get_post_meta( $product_id, '_app_only', true ) !== 'yes' ) return;

    // Disable ALL caching for app-only product pages (content differs by PWA cookie)
    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }

    // If NOT PWA — return 404
    if ( ! is_pwa_request() ) {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
    }
}

// 8. Hide app-only products from Relevanssi search (non-PWA visitors)
// OPTIMIZED: Filter out app-only products at SQL level using post__not_in
add_filter( 'relevanssi_hits_filter', 'rupills_filter_app_only_relevanssi_sql' );
function rupills_filter_app_only_relevanssi_sql( $data ) {
    if ( is_admin() || is_pwa_request() ) return $data;
    
    $app_only_ids = rupills_get_app_only_product_ids();
    
    if ( empty( $app_only_ids ) ) return $data;
    
    // Remove app-only products from results
    foreach ( $data[0] as $key => $hit ) {
        if ( in_array( $hit->ID, $app_only_ids, true ) ) {
            unset( $data[0][$key] );
        }
    }
    
    // Reindex array
    $data[0] = array_values( $data[0] );
    
    return $data;
}

// 9. Hide app-only products from WooCommerce REST API (non-PWA visitors)
add_filter( 'woocommerce_rest_product_object_query', 'rupills_filter_app_only_rest' );
function rupills_filter_app_only_rest( $args ) {
    if ( is_pwa_request() ) return $args;

    if ( ! isset( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
        $args['meta_query'] = [];
    }

    $args['meta_query'][] = array(
        'relation' => 'OR',
        array(
            'key'     => '_app_only',
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key'     => '_app_only',
            'value'   => 'yes',
            'compare' => '!=',
        ),
    );

    return $args;
}

// ============ Footer App Badges (Google Play & App Store style) ============

add_action('flatsome_absolute_footer_primary', 'rupills_footer_app_badges');
function rupills_footer_app_badges() {
    // Не показываем бейджи внутри PWA — приложение уже установлено
    if (function_exists('is_pwa_request') && is_pwa_request()) {
        return;
    }

    $theme_uri = get_stylesheet_directory_uri();
    $install_url = '/app/';
    $ver = '3';
    ?>
    <div class="app-badges-footer">
        <a href="<?php echo esc_url($install_url); ?>" class="app-badge-link" title="Get it on Google Play">
            <img src="<?php echo esc_url($theme_uri . '/assets/badge-google-play.svg?v=' . $ver); ?>"
                 alt="Get it on Google Play" width="135" height="40" loading="lazy">
        </a>
        <a href="<?php echo esc_url($install_url); ?>" class="app-badge-link" title="Download on the App Store">
            <img src="<?php echo esc_url($theme_uri . '/assets/badge-app-store.svg?v=' . $ver); ?>"
                 alt="Download on the App Store" width="120" height="40" loading="lazy">
        </a>
    </div>
    <?php
}

// CSS для бейджей в футере
add_action('wp_head', 'rupills_footer_app_badges_css', 99);
function rupills_footer_app_badges_css() {
    ?>
    <style>
    .app-badges-footer {
        display: flex;
        gap: 10px;
        margin-top: 12px;
        flex-wrap: wrap;
        align-items: center;
    }
    .app-badge-link {
        display: inline-block;
        transition: opacity .2s ease, transform .15s ease;
        line-height: 0;
    }
    .app-badge-link:hover {
        opacity: .85;
        transform: scale(1.03);
    }
    .app-badge-link:active {
        transform: scale(.97);
    }
    .app-badge-link img {
        height: 40px;
        width: auto;
        border-radius: 5px;
    }
    @media (max-width: 549px) {
        .app-badges-footer {
            justify-content: center;
            margin-top: 10px;
        }
        .app-badge-link img {
            height: 36px;
        }
    }
    </style>
    <?php
}

// ============ End PWA Support ============

// ============ Unified Login/Register: auto-create account on WooCommerce login ============

add_filter( 'authenticate', 'rupills_auto_register_on_login', 25, 3 );
function rupills_auto_register_on_login( $user, $username, $password ) {
    // Only for WooCommerce login form submissions
    if ( ! isset( $_POST['woocommerce-login-nonce'] ) ) {
        return $user;
    }

    // Only if authentication failed (user not found)
    if ( ! is_wp_error( $user ) ) {
        return $user;
    }

    $error_codes = $user->get_error_codes();
    $is_user_not_found = in_array( 'invalid_email', $error_codes, true )
                      || in_array( 'invalid_username', $error_codes, true );

    if ( ! $is_user_not_found ) {
        return $user; // wrong password or other error — don't auto-register
    }

    // Only auto-register if the provided login is a valid email
    if ( ! is_email( $username ) ) {
        return $user;
    }

    // Double-check: email should truly not exist
    if ( email_exists( $username ) ) {
        return $user;
    }

    // Create new WooCommerce customer (username auto-generated from email)
    $new_customer_id = wc_create_new_customer( $username, '', $password );

    if ( is_wp_error( $new_customer_id ) ) {
        return $new_customer_id;
    }

    // Return the newly created user for wp_signon to proceed
    return get_user_by( 'id', $new_customer_id );
}

// ============ End Unified Login/Register ============

// Cache exclusions for payment/cart/checkout pages are configured
// directly in LiteSpeed Cache plugin settings (cache-exc option).
// No hooks needed — see LiteSpeed > Cache > Do Not Cache URIs.

// ============ Payment Reminder Emails for Blockonomics (Bitcoin) ============

/**
 * Проверяет, есть ли у этого email более новый неоплаченный заказ (Blockonomics).
 * Если есть — напоминание для старого заказа не нужно, чтобы не засорять почту.
 */
function rupills_has_newer_unpaid_order($order_id, $email) {
    $newer = wc_get_orders([
        'billing_email' => $email,
        'status'        => ['pending', 'on-hold'],
        'payment_method' => 'blockonomics',
        'limit'         => 1,
        'orderby'       => 'date',
        'order'         => 'DESC',
        'return'        => 'ids',
    ]);

    return !empty($newer) && $newer[0] != $order_id;
}

add_action('woocommerce_checkout_order_processed', 'rupills_schedule_payment_reminders', 10, 3);
function rupills_schedule_payment_reminders($order_id, $posted_data = null, $order = null) {
    if (!$order) {
        $order = wc_get_order($order_id);
    }
    if (!$order) return;

    if ($order->get_payment_method() !== 'blockonomics') return;

    // Читаем задержки напоминаний из общих настроек, при отсутствии — используем дефолтные значения
    $delays = [
        1 => 30 * MINUTE_IN_SECONDS,
        2 => DAY_IN_SECONDS,
    ];

    if (function_exists('rupills_email_settings')) {
        $settings = rupills_email_settings();
        if (!empty($settings['payment_reminders']['reminder_delays']) && is_array($settings['payment_reminders']['reminder_delays'])) {
            $configured = $settings['payment_reminders']['reminder_delays'];
            foreach ($configured as $num => $delay) {
                $num = (int) $num;
                if ($num >= 1 && $num <= 5 && $delay > 0) {
                    $delays[$num] = (int) $delay;
                }
            }
        }
    }

    if (!as_next_scheduled_action('rupills_send_payment_reminder', [$order_id, 1], 'rupills-payment-reminders') && !empty($delays[1])) {
        as_schedule_single_action(
            time() + (int) $delays[1],
            'rupills_send_payment_reminder',
            [$order_id, 1],
            'rupills-payment-reminders'
        );
    }

    if (!as_next_scheduled_action('rupills_send_payment_reminder', [$order_id, 2], 'rupills-payment-reminders') && !empty($delays[2])) {
        as_schedule_single_action(
            time() + (int) $delays[2],
            'rupills_send_payment_reminder',
            [$order_id, 2],
            'rupills-payment-reminders'
        );
    }
}

add_action('rupills_send_payment_reminder', 'rupills_process_payment_reminder', 10, 2);
function rupills_process_payment_reminder($order_id, $reminder_num) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    if (!in_array($order->get_status(), ['pending', 'on-hold'])) return;

    $meta_key = '_payment_reminder_' . intval($reminder_num) . '_sent';
    if ($order->get_meta($meta_key)) return;

    $email = $order->get_billing_email();
    if ($email && rupills_has_newer_unpaid_order($order_id, $email)) return;

    if (!class_exists('Blockonomics')) {
        $plugin_path = WP_PLUGIN_DIR . '/blockonomics-bitcoin-payments/php/Blockonomics.php';
        if (file_exists($plugin_path)) {
            include_once $plugin_path;
        } else {
            return;
        }
    }

    $blockonomics = new Blockonomics();
    $payment_url = $blockonomics->get_order_checkout_url($order_id);
    if (empty($payment_url)) return;

    $lang = $order->get_meta('wpml_language');
    if (empty($lang)) {
        $lang = get_post_meta($order_id, 'wpml_language', true);
    }
    if (empty($lang)) {
        $lang = 'en';
    }

    $name  = esc_html($order->get_billing_first_name());
    $num   = $order->get_order_number();
    $total = $order->get_formatted_order_total();
    $url   = esc_url($payment_url);

    $btn_label = ($lang === 'ru') ? 'Оплатить заказ' : 'Pay Now';
    $btn_html  = '<div style="text-align:center;margin:25px 0;">'
        . '<a href="' . $url . '" style="display:inline-block;padding:14px 35px;background-color:#29ABE2;color:#ffffff;text-decoration:none;border-radius:6px;font-size:16px;font-weight:bold;">' . $btn_label . '</a>'
        . '</div>'
        . '<p style="font-size:12px;color:#888;text-align:center;"><a href="' . $url . '">' . $url . '</a></p>';

    $urgency_html = '<div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:14px 18px;margin:20px 0;text-align:center;">'
        . '<span style="font-size:18px;">⏰</span> '
        . '<strong style="color:#856404;font-size:14px;">'
        . (($lang === 'ru')
            ? 'Оплатите заказ, пока товары есть в наличии! Количество ограничено.'
            : 'Pay now while items are still in stock! Quantities are limited.')
        . '</strong>'
        . '</div>';

    if ($lang === 'ru') {
        $subject = sprintf('[Ru-Pills.com] Завершите оплату заказа #%s', $num);

        if ($reminder_num == 1) {
            $heading = sprintf('Завершите оплату заказа #%s', $num);
            $body = sprintf(
                'Здравствуйте, %s!<br><br>'
                . 'Мы заметили, что вы начали оформление заказа <strong>#%s</strong> на сумму <strong>%s</strong>, но оплата ещё не завершена.<br><br>'
                . '%s'
                . 'Нажмите на кнопку ниже, чтобы вернуться на страницу оплаты:%s'
                . 'Если у вас возникли вопросы, напишите нам в <a href="https://t.me/Ru_pills">Telegram</a>.<br><br>'
                . 'Спасибо,<br>Команда Ru-Pills.com',
                $name, $num, $total, $urgency_html, $btn_html
            );
        } else {
            $heading = sprintf('Напоминание: заказ #%s ожидает оплаты', $num);
            $body = sprintf(
                'Здравствуйте, %s!<br><br>'
                . 'Напоминаем, что ваш заказ <strong>#%s</strong> на сумму <strong>%s</strong> всё ещё ожидает оплаты.<br><br>'
                . '%s'
                . 'Заказ будет автоматически отменён, если оплата не поступит. Нажмите на кнопку ниже, чтобы завершить покупку:%s'
                . 'Если у вас возникли вопросы, напишите нам в <a href="https://t.me/Ru_pills">Telegram</a>.<br><br>'
                . 'Спасибо,<br>Команда Ru-Pills.com',
                $name, $num, $total, $urgency_html, $btn_html
            );
        }
    } else {
        $subject = sprintf('[Ru-Pills.com] Complete payment for order #%s', $num);

        if ($reminder_num == 1) {
            $heading = sprintf('Complete payment for order #%s', $num);
            $body = sprintf(
                'Hello, %s!<br><br>'
                . 'We noticed you started checkout for order <strong>#%s</strong> totalling <strong>%s</strong>, but the payment has not been completed yet.<br><br>'
                . '%s'
                . 'Click the button below to return to the payment page:%s'
                . 'If you have any questions, contact us via <a href="https://t.me/Ru_pills">Telegram</a>.<br><br>'
                . 'Thank you,<br>Ru-Pills.com Team',
                $name, $num, $total, $urgency_html, $btn_html
            );
        } else {
            $heading = sprintf('Reminder: order #%s is awaiting payment', $num);
            $body = sprintf(
                'Hello, %s!<br><br>'
                . 'This is a reminder that your order <strong>#%s</strong> totalling <strong>%s</strong> is still awaiting payment.<br><br>'
                . '%s'
                . 'The order will be automatically cancelled if payment is not received. Click the button below to complete your purchase:%s'
                . 'If you have any questions, contact us via <a href="https://t.me/Ru_pills">Telegram</a>.<br><br>'
                . 'Thank you,<br>Ru-Pills.com Team',
                $name, $num, $total, $urgency_html, $btn_html
            );
        }
    }

    do_action('wpml_switch_language', $lang);

    ob_start();
    wc_get_template(
        'emails/email-order-details.php',
        [
            'order'         => $order,
            'sent_to_admin' => false,
            'plain_text'    => false,
            'email'         => '',
        ]
    );
    $body .= ob_get_clean();

    $mailer = WC()->mailer();
    $email_content = $mailer->wrap_message($heading, $body);
    $result = $mailer->send($order->get_billing_email(), $subject, $email_content);

    do_action('wpml_switch_language', null);

    if ($result) {
        $order->update_meta_data($meta_key, current_time('mysql'));
        $order->save();

        $note = ($reminder_num == 1)
            ? 'Payment reminder #1 (30 min) sent to customer.'
            : 'Payment reminder #2 (24h) sent to customer.';
        $order->add_order_note($note);
    }
}

add_action('woocommerce_order_status_changed', 'rupills_cancel_payment_reminders', 10, 4);
function rupills_cancel_payment_reminders($order_id, $old_status, $new_status, $order) {
    $paid_or_closed = ['processing', 'completed', 'cancelled', 'refunded', 'failed', 'reserved'];
    if (!in_array($new_status, $paid_or_closed)) return;

    as_unschedule_all_actions('rupills_send_payment_reminder', [$order_id, 1], 'rupills-payment-reminders');
    as_unschedule_all_actions('rupills_send_payment_reminder', [$order_id, 2], 'rupills-payment-reminders');

    $email = $order->get_billing_email();
    if (!$email) return;

    $other_unpaid = wc_get_orders([
        'billing_email'  => $email,
        'status'         => ['pending', 'on-hold'],
        'payment_method' => 'blockonomics',
        'limit'          => -1,
        'return'         => 'ids',
        'exclude'        => [$order_id],
    ]);

    foreach ($other_unpaid as $other_id) {
        as_unschedule_all_actions('rupills_send_payment_reminder', [$other_id, 1], 'rupills-payment-reminders');
        as_unschedule_all_actions('rupills_send_payment_reminder', [$other_id, 2], 'rupills-payment-reminders');
    }
}

// ============ End Payment Reminder Emails ============

// ============ Custom Underpayment Email subject & heading (Blockonomics) ============

function rupills_is_blockonomics_underpayment($order) {
    if ($order->get_payment_method() !== 'blockonomics' || !$order->needs_payment()) {
        return false;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'blockonomics_payments';
    $paid  = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(paid_fiat), 0) FROM {$table} WHERE order_id = %d",
        $order->get_id()
    ));
    return $paid > 0;
}

function rupills_get_order_lang($order) {
    $lang = $order->get_meta('wpml_language');
    if (empty($lang)) {
        $lang = get_post_meta($order->get_id(), 'wpml_language', true);
    }
    return !empty($lang) ? $lang : 'en';
}

add_filter('woocommerce_email_subject_customer_invoice', 'rupills_underpayment_email_subject', 10, 2);
function rupills_underpayment_email_subject($subject, $order) {
    if (!rupills_is_blockonomics_underpayment($order)) return $subject;

    $num  = $order->get_order_number();
    $lang = rupills_get_order_lang($order);

    return ($lang === 'ru')
        ? sprintf('[Ru-Pills.com] Требуется доплата по заказу #%s', $num)
        : sprintf('[Ru-Pills.com] Additional payment required for order #%s', $num);
}

add_filter('woocommerce_email_heading_customer_invoice', 'rupills_underpayment_email_heading', 10, 2);
function rupills_underpayment_email_heading($heading, $order) {
    if (!rupills_is_blockonomics_underpayment($order)) return $heading;

    $num  = $order->get_order_number();
    $lang = rupills_get_order_lang($order);

    return ($lang === 'ru')
        ? sprintf('Частичная оплата получена — заказ #%s', $num)
        : sprintf('Partial payment received — order #%s', $num);
}

// ============ End Custom Underpayment Email ============

// ============ Checkout: настройки отображения методов оплаты ============

// Убираем описание Blockonomics
add_filter('woocommerce_gateway_description', 'rupills_remove_blockonomics_description', 10, 2);
function rupills_remove_blockonomics_description($description, $gateway_id) {
    if ($gateway_id === 'blockonomics') return '';
    return $description;
}

// CSS: скрываем описание + уменьшаем иконки + скрываем выбор если один метод
add_action('wp_head', 'rupills_checkout_payment_css', 999);
function rupills_checkout_payment_css() {
    if (!is_checkout() && !is_wc_endpoint_url('order-pay')) return;
    ?>
    <style>
        .payment_method_blockonomics .payment_box,
        .payment_method_blockonomics .payment_box * { display: none !important; }
        .wc_payment_method img {
            max-width: 60px !important;
            max-height: 40px !important;
            height: auto !important;
            width: auto !important;
        }
    </style>
    <?php
}

// Скрываем список методов оплаты визуально если доступен только один
add_filter('woocommerce_update_order_review_fragments', 'rupills_hide_single_payment_method');
function rupills_hide_single_payment_method($fragments) {
    $gateways = WC()->payment_gateways()->get_available_payment_gateways();
    if (count($gateways) <= 1) {
        $fragments['.woocommerce-checkout-payment'] = str_replace(
            'class="wc_payment_methods',
            'class="wc_payment_methods" style="display:none!important',
            $fragments['.woocommerce-checkout-payment'] ?? ''
        );
    }
    return $fragments;
}

// ============ End Checkout payment method ============

// ============ YITH Funds: fix partial payment + Deposit double-pay bug ============
// When partial payment reduces _order_total and the session expires,
// a customer can return and pay the reduced total with Deposit again,
// causing only the remainder to be charged instead of the full fund amount.
// This hook recalculates the correct fund deduction on payment completion.

add_action('woocommerce_payment_complete', 'rupills_fix_partial_fund_deduction', 5);
function rupills_fix_partial_fund_deduction($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    if ($order->get_payment_method() !== 'yith_funds') return;
    if ($order->get_meta('ywf_partial_payment') !== 'yes') return;

    $items_total = 0;
    foreach ($order->get_items() as $item) {
        $items_total += floatval($item->get_total()) + floatval($item->get_total_tax());
    }

    $shipping_items_total = 0;
    foreach ($order->get_items('shipping') as $item) {
        $shipping_items_total += floatval($item->get_total()) + floatval($item->get_total_tax());
    }

    $real_total = $items_total + $shipping_items_total;
    $recorded_funds = floatval($order->get_meta('_order_funds'));

    if ($recorded_funds >= $real_total) return;

    $customer_id = $order->get_user_id();
    if (!$customer_id) return;
    if (!class_exists('YITH_YWF_Customer')) return;

    $missing = $real_total - $recorded_funds;
    $customer_fund = new YITH_YWF_Customer($customer_id);
    $customer_fund->decrement_funds($missing);

    $order->update_meta_data('_order_funds', $real_total);
    $order->save();

    $order->add_order_note(sprintf(
        'Auto-fix: partial payment fund correction. Deducted additional €%.2f from customer #%d funds (total funds used: €%.2f).',
        $missing, $customer_id, $real_total
    ));
}

// ============ SEO Improvements ============

// --- Пункт 2: Auto meta description для товаров, если пустой в Yoast ---
add_filter( 'wpseo_metadesc', 'rupills_auto_product_metadesc' );
function rupills_auto_product_metadesc( $desc ) {
    if ( ! empty( $desc ) || ! is_product() ) {
        return $desc;
    }
    $product = wc_get_product( get_the_ID() );
    if ( ! $product ) {
        return $desc;
    }

    $title = $product->get_name();
    $lang  = apply_filters( 'wpml_current_language', 'en' );
    $brand = rupills_get_product_brand( $product );

    $cats      = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
    $category  = ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ? $cats[0] : '';

    if ( $lang === 'ru' ) {
        $desc = 'Купить ' . $title;
        if ( $brand ) $desc .= ' (' . $brand . ')';
        $desc .= ' в интернет-аптеке Ru-Pills.com.';
        if ( $category ) $desc .= ' Категория: ' . $category . '.';
        $desc .= ' Доставка в США, Великобританию, Европу и 120+ стран. Быстрая отправка.';
    } else {
        $desc = 'Buy ' . $title;
        if ( $brand ) $desc .= ' (' . $brand . ')';
        $desc .= ' online at Ru-Pills.com.';
        if ( $category ) $desc .= ' Category: ' . $category . '.';
        $desc .= ' International delivery to USA, UK, Europe and 120+ countries. Fast shipping.';
    }

    return mb_substr( $desc, 0, 160 );
}

// --- Пункт 4: Organization schema — контакты и соцсети ---
add_filter( 'wpseo_schema_organization', 'rupills_enhance_organization_schema' );
function rupills_enhance_organization_schema( $data ) {
    $data['contactPoint'] = array(
        '@type'       => 'ContactPoint',
        'contactType' => 'customer service',
        'url'         => 'https://t.me/Ru_pills',
        'availableLanguage' => array( 'English', 'Russian' ),
    );
    $data['sameAs'] = array(
        'https://t.me/Ru_pills',
    );
    return $data;
}

// --- Пункт 5: og:image по умолчанию, если нет своего ---
add_action( 'wpseo_add_opengraph_images', 'rupills_default_og_image' );
function rupills_default_og_image( $opengraph_images ) {
    if ( ! $opengraph_images->has_images() ) {
        $opengraph_images->add_image( home_url( '/icon_x512.png' ) );
    }
}

// --- Пункт 6: ItemList schema на страницах категорий ---
add_action( 'woocommerce_after_shop_loop', 'rupills_category_itemlist_schema' );
function rupills_category_itemlist_schema() {
    if ( ! is_product_category() && ! is_product_tag() ) {
        return;
    }
    global $wp_query;
    if ( empty( $wp_query->posts ) ) {
        return;
    }

    $items    = array();
    $position = 1;
    foreach ( $wp_query->posts as $post ) {
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'url'      => get_permalink( $post->ID ),
            'name'     => get_the_title( $post->ID ),
        );
    }

    $schema = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => single_term_title( '', false ),
        'numberOfItems'   => count( $items ),
        'itemListElement' => $items,
    );

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
}

// ============ End SEO Improvements ============

// ============ Custom Tracking Number & Email ============

add_filter( 'woocommerce_email_enabled_customer_completed_order', '__return_false' );

add_action( 'add_meta_boxes', 'rupills_tracking_meta_box' );
function rupills_tracking_meta_box() {
	$screens = array( 'shop_order', wc_get_page_screen_id( 'shop-order' ) );
	foreach ( $screens as $screen ) {
		add_meta_box(
			'rupills_tracking_box',
			'Трек-номер отправления',
			'rupills_tracking_meta_box_html',
			$screen,
			'side',
			'high'
		);
	}
}

function rupills_tracking_meta_box_html( $post_or_order ) {
	$order = ( $post_or_order instanceof WC_Order )
		? $post_or_order
		: wc_get_order( $post_or_order->ID );
	if ( ! $order ) return;

	$tracking = $order->get_meta( '_rupills_tracking_number' );
	if ( empty( $tracking ) ) {
		$tracking = $order->get_meta( '_wcst_order_trackno' );
	}
	$sent     = $order->get_meta( '_rupills_tracking_email_sent' );
	wp_nonce_field( 'rupills_tracking_save', 'rupills_tracking_nonce' );
	?>
	<p>
		<label for="rupills_tracking_number"><strong>Трек-номер:</strong></label><br>
		<input type="text" id="rupills_tracking_number"
		       name="rupills_tracking_number"
		       value="<?php echo esc_attr( $tracking ); ?>"
		       style="width:100%;" placeholder="например RA123456789CN" />
	</p>
	<p>
		<label>
			<input type="checkbox" name="rupills_resend_tracking_email" value="1" />
			Отправить (переотправить) письмо с трек-номером
		</label>
	</p>
	<?php if ( $sent ) : ?>
		<p style="color:#28a745;font-weight:600;margin:0;">
			&#9993; Письмо отправлено: <?php echo esc_html( $sent ); ?>
		</p>
	<?php endif; ?>
	<?php
}

add_action( 'woocommerce_process_shop_order_meta', 'rupills_tracking_save', 50, 1 );
function rupills_tracking_save( $order_id ) {
	if ( ! isset( $_POST['rupills_tracking_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['rupills_tracking_nonce'], 'rupills_tracking_save' ) ) {
		return;
	}

	$order        = wc_get_order( $order_id );
	$tracking_new = isset( $_POST['rupills_tracking_number'] )
		? sanitize_text_field( $_POST['rupills_tracking_number'] ) : '';
	$tracking_old = $order->get_meta( '_rupills_tracking_number' );

	$order->update_meta_data( '_rupills_tracking_number', $tracking_new );
	$order->save();

	$resend       = ! empty( $_POST['rupills_resend_tracking_email'] );
	$just_added   = empty( $tracking_old ) && ! empty( $tracking_new );
	$is_completed = $order->get_status() === 'completed';

	if ( ! empty( $tracking_new ) && ( $resend || ( $just_added && $is_completed ) ) ) {
		rupills_send_tracking_email( $order );
	}
}

add_action( 'woocommerce_order_status_completed', 'rupills_tracking_on_completed', 10, 1 );
function rupills_tracking_on_completed( $order_id ) {
	if ( ! empty( $_POST['rupills_tracking_nonce'] ) ) {
		return;
	}
	$order    = wc_get_order( $order_id );
	$tracking = $order->get_meta( '_rupills_tracking_number' );
	if ( ! empty( $tracking ) ) {
		rupills_send_tracking_email( $order );
	}
}

function rupills_send_tracking_email( $order ) {
	$tracking   = $order->get_meta( '_rupills_tracking_number' );
	if ( empty( $tracking ) ) return;

	$email      = $order->get_billing_email();
	$first_name = $order->get_billing_first_name();
	$order_id   = $order->get_id();
	$track_url  = 'https://parcelsapp.com/en/tracking/' . rawurlencode( $tracking );

	$lang = $order->get_meta( 'wpml_language' );
	if ( empty( $lang ) ) {
		$lang = get_post_meta( $order_id, 'wpml_language', true );
	}
	if ( empty( $lang ) ) {
		$lang = 'en';
	}

	if ( $lang === 'ru' ) {
		$subject = '[Ru-Pills.com] Ваш заказ #' . $order_id . ' отправлен';
		$heading = 'Ваш заказ отправлен!';
		$p1 = 'Здравствуйте, ' . esc_html( $first_name ) . '!';
		$p2 = 'Ваш заказ <strong>#' . $order_id . '</strong> отправлен!';
		$p3_label = 'Номер для отслеживания:';
		$btn_text = 'Отследить посылку';
		$note_heading = 'Обратите внимание:';
		$note1 = 'Посылка отправлена как личный подарок от частного лица без упоминания нашей компании. Внутри нет коммерческих документов (инвойсов, счетов и&nbsp;т.д.)&nbsp;&mdash; так и должно быть, не переживайте.';
		$note2 = 'После отправки мы, к сожалению, не можем повлиять на сроки и скорость доставки, а также не имеем возможности вносить изменения в отправление. По любым вопросам, связанным с доставкой, необходимо обращаться в почтовую службу.';
		$note3 = 'Если вам потребуются данные отправителя, просто ответьте на это письмо&nbsp;&mdash; мы предоставим всю необходимую информацию.';
		$thanks = 'Спасибо за покупку!';
		$team   = 'Команда Ru-Pills.com';
	} else {
		$subject = '[Ru-Pills.com] Your order #' . $order_id . ' has been shipped';
		$heading = 'Your order has been shipped!';
		$p1 = 'Hello, ' . esc_html( $first_name ) . '!';
		$p2 = 'Your order <strong>#' . $order_id . '</strong> has been shipped!';
		$p3_label = 'Tracking number:';
		$btn_text = 'Track your package';
		$note_heading = 'Please note:';
		$note1 = 'Your package has been sent as a personal gift from a private individual with no reference to our company. There are no commercial documents (invoices, receipts, etc.) inside&nbsp;&mdash; this is normal and expected.';
		$note2 = 'Once the package has been shipped, we are unfortunately unable to influence delivery times or make any changes to the shipment. For any delivery-related questions, please contact your local postal service directly.';
		$note3 = 'If you need the sender\'s details, simply reply to this email&nbsp;&mdash; we will provide all the necessary information.';
		$thanks = 'Thank you for your purchase!';
		$team   = 'Team Ru-Pills.com';
	}

	$body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f7f7f7;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f7f7f7;padding:40px 0;">
<tr><td align="center">

<table width="600" cellpadding="0" cellspacing="0" style="font-family:Arial,Helvetica,sans-serif;">

<tr><td style="padding:0 0 24px;text-align:center;">
<img src="https://ru-pills.com/wp-content/uploads/2022/12/logo_new.png" alt="Ru-Pills.com" style="max-width:160px;height:auto;" />
</td></tr>

<tr><td>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">

<tr><td style="background:#3498db;padding:28px 40px;text-align:center;">
<h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:0.3px;">' . $heading . '</h1>
</td></tr>

<tr><td style="padding:32px 40px 12px;">
<p style="margin:0 0 6px;font-size:15px;color:#3c3c3c;line-height:1.6;">' . $p1 . '</p>
<p style="margin:0 0 24px;font-size:15px;color:#3c3c3c;line-height:1.6;">' . $p2 . '</p>

<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2ecf4;border-radius:6px;margin-bottom:28px;">
<tr><td style="background:#f4f9fd;padding:18px 22px;">
<p style="margin:0 0 4px;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:0.5px;">' . $p3_label . '</p>
<p style="margin:0;font-size:20px;font-weight:700;color:#3498db;letter-spacing:0.5px;">' . esc_html( $tracking ) . '</p>
</td></tr>
</table>

<table cellpadding="0" cellspacing="0" style="margin:0 auto 32px;">
<tr><td style="background:#3498db;border-radius:6px;text-align:center;">
<a href="' . esc_url( $track_url ) . '" style="display:inline-block;color:#ffffff;text-decoration:none;padding:14px 40px;font-size:16px;font-weight:600;font-family:Arial,Helvetica,sans-serif;">' . $btn_text . '</a>
</td></tr>
</table>
</td></tr>

<tr><td style="padding:0 40px 32px;">
<div style="background:#fff8e1;border-left:4px solid #ffc107;border-radius:4px;padding:16px 18px;">
<p style="margin:0 0 10px;font-weight:700;font-size:14px;color:#856404;">' . $note_heading . '</p>
<p style="margin:0 0 10px;font-size:13px;color:#856404;line-height:1.6;">' . $note1 . '</p>
<p style="margin:0 0 10px;font-size:13px;color:#856404;line-height:1.6;">' . $note2 . '</p>
<p style="margin:0;font-size:13px;color:#856404;line-height:1.6;">' . $note3 . '</p>
</div>
</td></tr>

<tr><td style="padding:20px 40px;text-align:center;border-top:1px solid #eee;">
<p style="margin:0;font-size:15px;font-weight:600;color:#3c3c3c;">' . $thanks . '</p>
</td></tr>

</table>
</td></tr>

<tr><td style="padding:20px 0 0;text-align:center;font-size:12px;color:#aaa;font-family:Arial,Helvetica,sans-serif;">
' . $team . '
</td></tr>

</table>

</td></tr></table>
</body></html>';

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	wp_mail( $email, $subject, $body, $headers );

	$order->update_meta_data( '_rupills_tracking_email_sent', current_time( 'd.m.Y H:i' ) );
	$order->save();
}

// ============ End Custom Tracking Number & Email ============

// ============ Tracking Number on My Account / View Order ============

add_action( 'woocommerce_order_details_after_order_table', 'rupills_tracking_on_order_details', 10, 1 );
function rupills_tracking_on_order_details( $order ) {
	$tracking = $order->get_meta( '_rupills_tracking_number' );
	if ( empty( $tracking ) ) {
		$tracking = $order->get_meta( '_wcst_order_trackno' );
	}
	if ( empty( $tracking ) ) return;

	$track_url = 'https://parcelsapp.com/en/tracking/' . rawurlencode( $tracking );
	$lang = get_user_locale();
	$is_ru = ( strpos( $lang, 'ru' ) === 0 );

	$label     = $is_ru ? 'Трек-номер' : 'Tracking number';
	$btn_text  = $is_ru ? 'Отследить посылку' : 'Track your package';
	?>
	<section class="rupills-tracking-section" style="margin-top:24px;padding:20px;background:#f8f9fa;border-radius:8px;border:1px solid #e0e0e0;">
		<h2 style="font-size:16px;margin:0 0 12px;font-weight:700;"><?php echo esc_html( $label ); ?></h2>
		<p style="font-size:20px;font-weight:700;color:#3498db;margin:0 0 14px;letter-spacing:0.5px;"><?php echo esc_html( $tracking ); ?></p>
		<a href="<?php echo esc_url( $track_url ); ?>" target="_blank" rel="noopener"
		   style="display:inline-block;background:#3498db;color:#fff;text-decoration:none;padding:10px 28px;border-radius:5px;font-size:15px;font-weight:600;">
			<?php echo esc_html( $btn_text ); ?>
		</a>
	</section>
	<?php
}

// ============ End Tracking Number on My Account / View Order ============

// ============ Search Orders by Tracking Number ============

// HPOS: add tracking meta key to searchable meta fields
add_filter( 'woocommerce_order_table_search_query_meta_keys', 'rupills_add_tracking_to_hpos_search' );
function rupills_add_tracking_to_hpos_search( $meta_keys ) {
	$meta_keys[] = '_rupills_tracking_number';
	return $meta_keys;
}

// Legacy (post-based): add tracking meta key to searchable fields
add_filter( 'woocommerce_shop_order_search_fields', 'rupills_add_tracking_to_legacy_search' );
function rupills_add_tracking_to_legacy_search( $search_fields ) {
	$search_fields[] = '_rupills_tracking_number';
	return $search_fields;
}

// ============ End Search Orders by Tracking Number ============

// ============ Relevanssi XSS sanitization (CVE-2025-4054 / CVE-2025-5016) ============

add_filter( 'relevanssi_excerpt_query', 'rupills_sanitize_relevanssi_query' );
add_filter( 'relevanssi_search_ok', 'rupills_sanitize_highlight_qv', 1 );

function rupills_sanitize_relevanssi_query( $query ) {
	if ( is_array( $query ) ) {
		return array_map( 'wp_strip_all_tags', $query );
	}
	return wp_strip_all_tags( $query );
}

function rupills_sanitize_highlight_qv( $ok ) {
	global $wp_query;
	if ( isset( $wp_query->query_vars['highlight'] ) ) {
		$wp_query->query_vars['highlight'] = wp_strip_all_tags(
			$wp_query->query_vars['highlight']
		);
	}
	return $ok;
}

// ============ End Relevanssi XSS sanitization ============

// ============ Translate WooCommerce store notice per WPML language ============
add_filter( 'option_woocommerce_demo_store_notice', 'rupills_translate_store_notice' );
function rupills_translate_store_notice( $notice ) {
	$lang = apply_filters( 'wpml_current_language', 'en' );
	if ( $lang === 'ru' ) {
		return 'Пауза в отправке: 26 февраля – 13 марта. Оформляйте заказ сейчас — отправим в первую очередь после возобновления! Все заказы принимаются и обрабатываются как обычно.';
	}
	return $notice;
}

// ============ Translate search placeholder per WPML language ============
add_filter( 'theme_mod_search_placeholder', 'rupills_translate_search_placeholder' );
function rupills_translate_search_placeholder( $value ) {
	$lang = apply_filters( 'wpml_current_language', 'en' );
	if ( $lang === 'ru' ) {
		return 'Поиск по названию, действующему веществу или заболеванию...';
	}
	return 'Search by drug name, active ingredient, or condition...';
}

// ============ Validate billing email MX records on checkout ============
add_action( 'woocommerce_after_checkout_validation', 'rupills_validate_email_mx', 10, 2 );
function rupills_validate_email_mx( $data, $errors ) {
	$email = isset( $data['billing_email'] ) ? $data['billing_email'] : '';
	if ( empty( $email ) || ! is_email( $email ) ) {
		return;
	}

	$domain = substr( $email, strrpos( $email, '@' ) + 1 );

	$has_mx = checkdnsrr( $domain, 'MX' );
	if ( $has_mx ) {
		return;
	}

	$has_a = checkdnsrr( $domain, 'A' );
	if ( $has_a ) {
		return;
	}

	$lang = apply_filters( 'wpml_current_language', 'en' );
	if ( $lang === 'ru' ) {
		$errors->add( 'validation', 'Домен электронной почты не существует или не принимает письма. Пожалуйста, проверьте адрес.' );
	} else {
		$errors->add( 'validation', 'The email domain does not exist or cannot receive emails. Please check your email address.' );
	}
}
// ============ End Validate billing email MX ============

// ============ Redirect empty search to homepage ============
add_action( 'template_redirect', 'rupills_redirect_empty_search' );
function rupills_redirect_empty_search() {
	if ( is_search() && isset( $_GET['s'] ) && trim( $_GET['s'] ) === '' ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
}

// ============ Prevent empty search form submission (JS) ============
add_action( 'wp_footer', 'rupills_prevent_empty_search_js' );
function rupills_prevent_empty_search_js() {
	?>
	<script>
	(function(){
		document.querySelectorAll('form.searchform').forEach(function(form){
			form.addEventListener('submit', function(e){
				var input = form.querySelector('input[name="s"]');
				if( input && input.value.trim() === '' ){
					e.preventDefault();
					input.focus();
					input.classList.add('shake');
					setTimeout(function(){ input.classList.remove('shake'); }, 600);
				}
			});
		});
	})();
	</script>
	<style>
	@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}
	.shake{animation:shake .4s ease}
	</style>
	<?php
}

// ============ Relevanssi: auto-expand search with pharma name variants ============
add_filter( 'relevanssi_implicit_operator', 'rupills_maybe_or_operator' );
function rupills_maybe_or_operator( $operator ) {
	global $rupills_search_expanded;
	return ! empty( $rupills_search_expanded ) ? 'OR' : $operator;
}

add_filter( 'relevanssi_modify_wp_query', 'rupills_relevanssi_pharma_variants' );
function rupills_relevanssi_pharma_variants( $query ) {
	global $rupills_search_expanded;
	$rupills_search_expanded = false;

	if ( empty( $query->query_vars['s'] ) ) {
		return $query;
	}

	$original = $query->query_vars['s'];

	// Only apply pharma swaps for single-word queries.
	// Multi-word queries (e.g. "Vitamin C") can produce misleading results (e.g. "Vitamin K").
	if ( preg_match( '/\s/', trim( $original ) ) ) {
		return $query;
	}

	$lower    = mb_strtolower( $original );
	$variants = array();

	// --- Bidirectional swaps (both directions common in pharma typos) ---
	$bi_swaps = array(
		'i'  => 'y',   // Mildronate / Myldronate
		'y'  => 'i',
		'ph' => 'f',   // Phenibut / Fenibut, Phosphogliv / Fosfogliv
		'f'  => 'ph',
		'c'  => 'k',   // Corvalol / Korvalol, Cyclodol / Kyklodol
		'k'  => 'c',
		'x'  => 'ks',  // Xefocam / Ksefokam
		'ks' => 'x',
	);

	foreach ( $bi_swaps as $from => $to ) {
		if ( mb_strpos( $lower, $from ) !== false ) {
			$variant = str_ireplace( $from, $to, $original );
			if ( mb_strtolower( $variant ) !== $lower ) {
				$variants[] = $variant;
			}
		}
	}

	// --- One-directional swaps (reverse direction too aggressive) ---
	$uni_swaps = array(
		'th' => 't',   // Methionine / Metionine, Thiamine / Tiamine
		'w'  => 'v',   // Warfarin / Varfarin
		'z'  => 's',   // Omeprazole / Omeprasole, Azithromycin / Asithromycin
	);

	foreach ( $uni_swaps as $from => $to ) {
		if ( mb_strpos( $lower, $from ) !== false ) {
			$variant = str_ireplace( $from, $to, $original );
			if ( mb_strtolower( $variant ) !== $lower ) {
				$variants[] = $variant;
			}
		}
	}

	// c → s before e/i (Cetirizine / Setirizine, Ciprofloxacin / Siprofloxacin)
	if ( preg_match( '/c[ei]/i', $original ) ) {
		$variant = preg_replace( '/c(?=[ei])/i', 's', $original );
		if ( mb_strtolower( $variant ) !== $lower ) {
			$variants[] = $variant;
		}
	}

	// -ine ↔ -in at word boundary (Amitriptyline / Amitriptylin)
	if ( preg_match( '/ine\b/i', $original ) ) {
		$variant = preg_replace( '/ine\b/i', 'in', $original );
		if ( mb_strtolower( $variant ) !== $lower ) {
			$variants[] = $variant;
		}
	} elseif ( preg_match( '/(?<!e)in\b/i', $original ) ) {
		$variant = preg_replace( '/in\b/i', 'ine', $original );
		if ( mb_strtolower( $variant ) !== $lower ) {
			$variants[] = $variant;
		}
	}

	// Double consonant → single (Amoxicillin / Amoxicilin)
	$doubles = array( 'll', 'ss', 'ff', 'pp', 'tt', 'cc', 'nn', 'mm', 'rr' );
	foreach ( $doubles as $dd ) {
		if ( mb_strpos( $lower, $dd ) !== false ) {
			$variant = str_ireplace( $dd, mb_substr( $dd, 0, 1 ), $original );
			if ( mb_strtolower( $variant ) !== $lower ) {
				$variants[] = $variant;
			}
		}
	}

	// Deduplicate
	$unique = array();
	foreach ( $variants as $v ) {
		$vl = mb_strtolower( $v );
		if ( $vl !== $lower && ! isset( $unique[ $vl ] ) ) {
			$unique[ $vl ] = $v;
		}
	}

	$unique = array_slice( $unique, 0, 10, true );

	if ( ! empty( $unique ) ) {
		$expanded = $original . ' ' . implode( ' ', array_values( $unique ) );
		$query->query_vars['s'] = $expanded;
		$query->query_vars['operator'] = 'OR';
		$rupills_search_expanded       = true;
	}

	return $query;
}
// ============ End Relevanssi pharma name variants ============

// ============ Relevanssi: index yikes_woo_products_tabs text content ============
add_filter( 'relevanssi_content_to_index', 'rupills_index_yikes_tabs', 10, 2 );
function rupills_index_yikes_tabs( $content, $post ) {
	if ( 'product' !== $post->post_type ) {
		return $content;
	}

	$tabs = get_post_meta( $post->ID, 'yikes_woo_products_tabs', true );
	if ( ! is_array( $tabs ) ) {
		return $content;
	}

	$parts = array();
	foreach ( $tabs as $tab ) {
		$text = isset( $tab['content'] ) ? trim( wp_strip_all_tags( $tab['content'] ) ) : '';
		if ( mb_strlen( $text ) > 5 ) {
			$parts[] = $text;
		}
	}

	if ( ! empty( $parts ) ) {
		$content .= ' ' . implode( ' ', $parts );
	}

	return $content;
}
// ============ End Relevanssi yikes tabs indexing ============

// ============ WPML: hide language switcher on Blockonomics payment page ============
add_filter( 'icl_ls_languages', 'rupills_hide_lang_switcher_on_blockonomics_payment' );
function rupills_hide_lang_switcher_on_blockonomics_payment( $languages ) {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return $languages;
	}

	$payment_page_id = wc_get_page_id( 'payment' );

	if ( $payment_page_id && is_page( $payment_page_id ) ) {
		// Returning empty array hides all languages in the switcher.
		return array();
	}

	return $languages;
}
// ============ End WPML: hide language switcher on Blockonomics payment page ============