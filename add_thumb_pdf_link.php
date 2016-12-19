<?php
/*
Plugin Name: サムネがぽん
Description: ImageMagickで生成されたPDFのサムネイルをつけた形で、投稿にPDFのリンクが設置できるプラグイン
Author: ah-kutsu
Version: 1.0
Author URI: http://ah-kutu.net
*/

// ---------------------------------------------------------------
// 前提条件
// ---------------------------------------------------------------
// WordPress4.7以上
// ImageMagickが有効化されていること（メディアライブラリーでPDFをアップロード時にサムネイルが表示される状態であること）
// ---------------------------------------------------------------

// ---------------------------------------------------------------
// 注意
// ---------------------------------------------------------------
// このプラグインを有効にすると、メディアライブラリーからPDFを追加した場合にもれなくサムネイルが強制的に出力されます。お手数ですが、不要なサムネイルは手動にて削除ください。
// （後々任意でサムネイルするかどうかを設定できる機能など追加予定）
// ---------------------------------------------------------------


// ---------------------------------------------------------------
// メディアライブラリーからPDFファイルを投稿に挿入したときにサムネイル付でリンクを追加
// ---------------------------------------------------------------
function add_thumb_pdf_link($html, $id){
	if ( extension_loaded('imagick') && get_bloginfo('version') >= 4.7 ) { //imageMagickが動いているか、WPバージョンが4.7以上か
		//添付ファイルの情報を取得
		$file_url   = wp_get_attachment_url($id); //添付ファイルURL
		$file_title = get_the_title($id); //添付ファイルタイトル
		//添付ファイルの拡張子を取得
		$split_url  = explode('.', $file_url); //添付ファイルのURLをピリオドで分割
		$extension  = array_pop($split_url); //分割されたURLの最後の要素を取得 = 拡張子を格納
		//拡張子がpdfの場合はサムネイル付きのリンクを返す
		if ( $extension === 'pdf' ) {
			$thumb_path = wp_get_attachment_image($id, 'thumbnail');
			$html       = '<a href="'.$file_url.'" target="_blank" class="pdf_link_thumb_on">'.$thumb_path.$file_title.'</a>';
		}
	}
	return $html;
}
add_filter('media_send_to_editor','add_thumb_pdf_link',10,3);

?>