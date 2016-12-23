<?php
/*
Plugin Name: サムネがぽんっ
Description: ImageMagickで生成されたPDFのサムネイル画像を投稿に挿入することができるプラグイン
Author: アクツ
Version: 1.0
Author URI: http://ah-kutsu.net
*/

// ---------------------------------------------------------------
// 前提条件
// ---------------------------------------------------------------
// WordPress4.7以上
// ImageMagickが有効化されていること（メディアライブラリーでPDFをアップロード時にサムネイルが表示される状態であること）
// ---------------------------------------------------------------

// ---------------------------------------------------------------
// 要件チェック
// ---------------------------------------------------------------
function thumbpon_requirement_check(){
	$i   = 0;
	$err = array();
	// システムのバージョンチェック
	if ( get_bloginfo('version') < 4.7 ) {
		$err[$i] = array(
			'id'      => 'wpversion',
			'message' => 'WPのバージョンが4.7未満です。バージョンアップください。',
		);
		$i++;
	}
	//ImageMagickバージョンチェック
	if ( !extension_loaded('imagick') ) {
		$err[$i] = array(
			'id'      => 'imagemagick',
			'message' => 'ImageMagickが有効化されていません。サーバーの設定をご確認ください',
		);
		$i++;
	}
	return $err;
}

// ---------------------------------------------------------------
// プラグイン有効時に要件チェックを行い、エラーの場合はメッセージを表示
// ---------------------------------------------------------------
register_activation_hook( __FILE__, 'thumbpon_activation_check' );
function thumbpon_activation_check() {
	$errs = thumbpon_requirement_check();
	if ( $errs ) {
		$wp_error = new WP_Error();
		foreach ( $errs as $err ) {
			$wp_error->add( $err['id'], $err['message']);
		}
		set_transient( 'thumbpon_activation_check_error', $wp_error->get_error_messages(), 10 );
	}
}
// エラーメッセージ表示
// --------------------
add_action( 'admin_notices', 'thumbpon_err_msg_view' );
function thumbpon_err_msg_view() {
	if ( $messages = get_transient( 'thumbpon_activation_check_error' ) ) {
		$html  = '<div class="error"><p style="font-weight:bold;">「サムネがぽんっ」はこのままでは動きません：</p><ul style="margin-top:0;">';
		foreach( $messages as $message ) {
			$html .= '<li>'.esc_html($message).'</li>';
		}
		$html .= '</ul></div>';
		echo $html;
	}
}

// ---------------------------------------------------------------
// サムネイル挿入判断用のフィールドを追加
// ---------------------------------------------------------------
// フィールドを設置
// --------------------
add_filter('attachment_fields_to_edit', 'thumbpon_add_attachment_field', 10, 2);
function thumbpon_add_attachment_field( $form_fields, $post ) {
	// if ( count(thumbpon_requirement_check()) > 0 ) { die(); } //エラーがあった場合は処理を終了
	//ファイル形式がPDFだったらサムネ挿入フラグ用のフィールドを追加
	if ( preg_match('/pdf/', $post->post_mime_type) ) {
		$selected = get_post_meta( $post->ID, 'insert_thumb', true );
		if( !$selected || $selected == 'none' ) {
			$checked_off = ' checked="checked"';
		} else {
			$checked_on = ' checked="checked"';
		}
		$html  = "<div class='thumbpon_insert_thumb'>";
		$html .= "<input type='radio' name='attachments[$post->ID][insert_thumb]' id='insert_thumb-option-1' value='none'$checked_on />";
		$html .= "<label for='insert_thumb-option-1'>挿入しない</label>";
		$html .= "<input type='radio' name='attachments[$post->ID][insert_thumb]' id='insert_thumb-option-2' value='insert'$checked_off />";
		$html .= "<label for='insert_thumb-option-2'>挿入する</label>";
		$html .= "</div>";
		$form_fields['insert_thumb'] = array(
			'input' => 'html',
			'html'  => $html,
			'label' => 'サムネイルは',
			'helps' => 'サムネイル付でリンクを挿入するか選択ください'
		);
		return $form_fields;
	}
}
// フィールドの保存
// --------------------
add_filter('attachment_fields_to_save', 'thumbpon_save_field', 10, 2);
function thumbpon_save_field($post, $attachment){
	if ( isset($attachment['insert_thumb']) ) {
		update_post_meta($post['ID'], 'insert_thumb', $attachment['insert_thumb']);
	}
	return $post;
}

// ---------------------------------------------------------------
// メディアライブラリーからPDFファイルを投稿に挿入したときにサムネイル付でリンクを追加
// ---------------------------------------------------------------
add_filter('media_send_to_editor','thumbpon_insert_thumb_to_editor', 10, 3);
function thumbpon_insert_thumb_to_editor($html, $id, $attachment){
	_log(get_post_meta($id));
	if ( !preg_match('/pdf/', get_post($id)->post_mime_type) ) {
		$thumb_path = wp_get_attachment_image($id, 'thumbnail');
		$html       = '<a href="'.$attachment['url'].'" target="_blank" class="thumbpon_item">'.$thumb_path.$attachment['post_title'].'</a>';
	}
	return $html;
}

?>