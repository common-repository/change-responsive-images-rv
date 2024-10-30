<?php 
namespace Packsystem;

class ChangeResponsiveImages{
	private $options;
	
	public function __construct(){
		if(is_admin()){
			$this->load_options();
			add_action( 'plugins_loaded', [$this, 'admin_hooks']);
		}else{
			add_filter('wp_calculate_image_srcset', [$this, 'calc_image_srcset'], 10, 5);
			add_filter('wp_calculate_image_sizes', [$this, 'calc_image_sizes'], 10, 5);
			add_filter('get_attachment_picture', [$this, 'get_attachment_picture'], 10, 4);
		}
	}
	
	public function load_options(){
		$this->options = (array) get_option('cri_options');
		$this->options['media_descriptions'] = (array) get_option('cri_media_descriptions');
	}
	
	public function get_attachment_picture($html, $attachment_id, $size, $attr){
		$this->load_options();
		
		if ( ! $image = wp_get_attachment_image_src( $attachment_id, $size ) ) {
			return '';
		}

		if ( ! is_array( $image_meta ) ) {
			$image_meta = wp_get_attachment_metadata( $attachment_id );
		}
		
		list($image_src, $width, $height) = $image;
		$size_array = [absint($width), absint($height)];
		
		$hwstring                   = image_hwstring( $width, $height );
		$size_class                 = $size;
		if ( is_array( $size_class ) ) {
			$size_class = join( 'x', $size_class );
		}
		//$attachment   = get_post( $attachment_id );
		$attr['class'] = "attachment-{$size_class} size-{$size_class} {$attr['class']}";
		$default_attr = array(
			'src'   => $image_src,
			'alt'   => trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ),
		);

		$attr = wp_parse_args( $attr, $default_attr );
		
		/**
		 * Filters the list of attachment image attributes.
		 *
		 * @since 2.8.0
		 *
		 * @param array        $attr       Attributes for the image markup.
		 * @param WP_Post      $attachment Image attachment post.
		 * @param string|array $size       Requested size. Image size or array of width and height values
		 *                                 (in that order). Default 'thumbnail'.
		 */
		$attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment, $size );
		$attr = array_map( 'esc_attr', $attr );
		
		$sources = apply_filters( 'wp_calculate_image_srcset', NULL, $size_array, $image_src, $image_meta, $attachment_id );
		
		
		$img_html = rtrim( "<img $hwstring" );
		foreach ( $attr as $name => $value ) {
			$img_html .= " $name=" . '"' . $value . '"';
		}
		$img_html .= ' />';
		
		usort($sources, function($a, $b){
			if ($a['value'] == $b['value']) return 0;
			return ($a['value'] < $b['value']) ? -1 : 1;
		});
		
		$html = '<picture>';
		foreach ($sources as $source) {
			$html.= '<source srcset="'.$source['url'].'" media="(max-width: '.$source['value'].'px)">';
		}
		$html.= $img_html;
		$html.= '</picture>';

		return $html;
	}
	
	public function calc_image_sizes($sizes, $size, $image_src, $image_meta, $attachment_id){
		$change = apply_filters('CRI_change_sizes_attr', true, $sizes, $size, $image_src, $image_meta, $attachment_id);
		if(!$change) return $sizes;
		if(!$this->options) $this->load_options();
		
		if(in_the_loop() && (is_home() || is_search() || is_archive())){
			$sizes = $this->options['default_size_attr_in_loop'];
			if(!$sizes) $sizes = '576px';
		}else{
			$sizes = $this->options['default_size_attr'];
			if(!$sizes) $sizes = '(max-width: 576px) 576px, (max-width: 768px) 768px, (max-width: 992px) 992px, (max-width: 1200px) 1200px, (max-width: 1600px) 1600px, 100vw';
		}
		
		return $sizes;
	}
	
	/**
 	 * Do not ignore when the aspect ratio is diferent.
 	 * @param array  $sources {
 	 *     One or more arrays of source data to include in the 'srcset'.
 	 *
 	 *     @type array $width {
 	 *         @type string $url        The URL of an image source.
 	 *         @type string $descriptor The descriptor type used in the image candidate string,
 	 *                                  either 'w' or 'x'.
 	 *         @type int    $value      The source width if paired with a 'w' descriptor, or a
 	 *                                  pixel density value if paired with an 'x' descriptor.
 	 *     }
 	 * }
 	 * @param array  $size_array    Array of width and height values in pixels (in that order).
 	 * @param string $image_src     The 'src' of the image.
 	 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
 	 * @param int    $attachment_id Image attachment ID or 0.
 	 */
	public function calc_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id){
		$change = apply_filters('CRI_change_srcset_attr', true, $sources, $size_array, $image_src, $image_meta, $attachment_id);
		if(!$change) return $sources;
		
		if(!$this->options) $this->load_options();
		
		$image_sizes = $image_meta['sizes'];
		
		$type_to_ignore = ['thumbnail', 'post-image'];
		
		$dirname = _wp_get_attachment_relative_path( $image_meta['file'] );
		if ($dirname) $dirname = trailingslashit( $dirname );
		$upload_dir    = wp_get_upload_dir();
		$image_baseurl = trailingslashit( $upload_dir['baseurl'] ) . $dirname;
		
		$max_srcset_image_width = apply_filters( 'max_srcset_image_width', 1920, $size_array );
		
		$src_matched = false;
		$sources = [];
		
		/**
		 * This foreach is documentend in wp-include/media.php Around Line #1138
		 * look for filter "wp_calculate_image_srcset"
		 */
		foreach ( $image_sizes as $i_name => $image ) {
			$is_src = false;
			
			if(!is_array($image)) continue;
			

			if ( ! $src_matched && false !== strpos( $image_src, $dirname . $image['file'] ) ) {
				$src_matched = $is_src = true;
			}

			if(!$is_src && !$this->options['media_descriptions'][$i_name]['responsive']) continue;
			
			if ( $max_srcset_image_width && $image['width'] > $max_srcset_image_width && ! $is_src ) {
				continue;
			}

			$source = [
				'url'        => $image_baseurl . $image['file'],
				'descriptor' => 'w',
				'value'      => $image['width'],
			];

			if ($is_src){
				$sources = [$image['width'] => $source] + $sources;
			}else{
				$sources[$image['width']] = $source;
			}
		}
		
		return $sources;
	}
	
	public function admin_hooks(){
		add_action('admin_init', [$this, 'internationalization']);
		add_action('admin_init', [$this, 'register_setting']);
		add_action('admin_menu', [$this, 'menu_settings']);
		add_action('post_edit_form_tag', [$this, 'allow_upload']);
		add_action('load-post.php', [$this, 'meta_boxes']);
		add_action('load-post-new.php', [$this, 'meta_boxes']);
	}
	/*
	* Load translations
	*/
	public function internationalization() {
		$domain = 'packsystem';

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		if ( $loaded = load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' ) ) {
			return $loaded;
		} else {
			load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
		}
	}
	
	public function register_setting(){
		register_setting('media-description', 'cri_media_descriptions');
		register_setting('media-description', 'cri_options');
	}
	
	public function menu_settings(){
		add_submenu_page('options-general.php', __('Media Options', 'packsystem'), __('Media Options', 'packsystem'), 'manage_options', 'media-description', [$this, 'media_description_page']);
	}
	
	public function media_description_page(){
		global $title;
		$medias = $this->options['media_descriptions'];
		if(!$medias) $medias = [];
		?>
		<div class="wrap">
			<h1><?php echo $title; ?></h1>
			<h2 class="title"><?php _e('Image Descriptions', 'packsystem') ?></h2>
			<p><?php _e('The sizes listed below determine the names and descriptions for the images.', 'packsystem') ?></p>
			<p><?php _e('The idea is to be easy for the users identify what kind of images they need to upload according to size.', 'packsystem') ?></p>
			<form novalidate action="options.php" method="post">
				<?php settings_fields( 'media-description' ); ?>
				<table class="form-table">
					<tbody class="options-media-php">
						<?php foreach (get_intermediate_image_sizes() as $name): 
							$name_attr = \esc_attr($name);
							$media = $medias[$name];
							if(!$media) $media = [];
							?>
							<tr>
								<th scope="row"><?php echo $name ?></th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span><?php echo $name ?></span></legend>
										<label for="_size_<?php echo $name_attr ?>_name" style="font-size:13px;"><strong><?php _e('Name', 'packsystem') ?></strong></label>
										<input name="cri_media_descriptions[<?php echo $name_attr ?>][name]" type="text" id="_size_<?php echo $name_attr ?>_name" value="<?php echo esc_attr($media['name']) ?>" class="regular-text">
										<br>
										<label for="_size_<?php echo $name_attr ?>_description" style="font-size:13px;"><strong><?php _e('Description', 'packsystem') ?></strong></label>
										<input name="cri_media_descriptions[<?php echo $name_attr ?>][description]" type="text" id="_size_<?php echo $name_attr ?>_description" value="<?php echo esc_attr($media['description']) ?>" class="regular-text">
									</fieldset>
									<input name="cri_media_descriptions[<?php echo $name_attr ?>][responsive]" type="checkbox" id="<?php echo $name_attr ?>_responsive" value="1" <?php checked($media['responsive'], 1) ?>>
									<label for="<?php echo $name_attr ?>_responsive"><?php _e('Use as a responsive option.') ?></label><br>
									<input name="cri_media_descriptions[<?php echo $name_attr ?>][replaceable]" type="checkbox" id="<?php echo $name_attr ?>_replaceable" value="1" <?php checked($media['replaceable'], 1) ?>>
									<label for="<?php echo $name_attr ?>_responsive"><?php _e('Permit user replace it.') ?></label>
								</td>
							</tr>
						<?php endforeach; ?>
						<tr>
							<th scope="row"><label for="default_size_attr"><?php _e("Default image attribute 'sizes'", 'packsystem') ?></label></th>
							<td>
								<input name="cri_options[default_size_attr]" type="text" id="default_size_attr" value="<?php echo esc_attr($this->options['default_size_attr']) ?>" class="large-text">
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="default_size_attr_in_loop"><?php _e("Default image attribute 'sizes' in loop", 'packsystem') ?></label></th>
							<td>
								<input name="cri_options[default_size_attr_in_loop]" type="text" id="default_size_attr_in_loop" value="<?php echo esc_attr($this->options['default_size_attr_in_loop']) ?>" class="large-text">
								<p class="description"><?php _e('Devs: Remember that to be in loop, you need to use query_posts() instead customs WP_queries.') ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Add Metabox Hooks
	 * @return NULL
	 */
	public function meta_boxes(){
		add_action('add_meta_boxes', [$this, 'meta_boxes_add'], 10, 2);
		add_action('edit_attachment', [$this, 'meta_boxes_save']);
	}
	
	/**
	 * Add Image Meta Boxes
	 * @param String $post_type 
	 * @param Object $post
	 */
	public function meta_boxes_add($post_type, $post){
		if($post_type != 'attachment' || !wp_attachment_is_image($post->ID)) return;
		
		add_meta_box(
			'ps_other_images', 
			__('All Images', 'packsystem'), 
			[$this, 'images'], 
			['attachment'], 'normal', 'high'
		);
	}
	
	public function images($post){
		if(!$this->options) $this->load_options();
		//replaceable
		$image_descriptions = $this->options['media_descriptions'];
		if(!$image_descriptions) $image_descriptions = [];
		$size_order = [
			'full',
			'small',
			'thumbnail',
			'medium',
			'medium_large',
			'large',
		];
		
		list($base_url, $filename) = $this->get_attachment_base_url($post->ID);
		
		$metadata = wp_get_attachment_metadata($post->ID);
		$images = $metadata['sizes'];
		if($image_descriptions){
			foreach(array_keys($images) as $i_slug){
				if(!$image_descriptions[$i_slug]['replaceable']){
					unset($images[$i_slug]);
				}
			}
		}
		
		$images['full'] = [
			'file' => $filename,
			'width' => $metadata['width'],
			'height' => $metadata['height']
		];
		
		$new_images = [];
		foreach ($size_order as $i_slug) {
			unset($size_order[$i_slug]);
			if(!$images[$i_slug]) continue;
			if(!$image_descriptions[$i_slug]) $image_descriptions[$i_slug] = ['name' => __('Image', 'packsystem').' '.$i_slug];
			elseif(!$image_descriptions[$i_slug]['name']) $image_descriptions[$i_slug]['name'] = __('Image', 'packsystem').' '.$i_slug;
			
			$image = $images[$i_slug];
			$descr = $image_descriptions[$i_slug];
			
			
			$new_images[$i_slug] = [
				$base_url.$image['file'],
				$image['width'],
				$image['height'],
				$descr['name'],
				$descr['description']
			];
			
			unset($image_descriptions[$i_slug], $images[$i_slug]);
		}
		
		if($images)
		foreach ($images as $i_slug => $image) {
			if(!$image_descriptions[$i_slug]) $image_descriptions[$i_slug] = ['name' => __('Image', 'packsystem').' '.$i_slug];
			elseif(!$image_descriptions[$i_slug]['name']) $image_descriptions[$i_slug]['name'] = __('Image', 'packsystem').' '.$i_slug;
			
			$image = $images[$i_slug];
			$descr = $image_descriptions[$i_slug];
			
			$new_images[$i_slug] = [
				$base_url.$image['file'],
				$image['width'],
				$image['height'],
				$descr['name'],
				$descr['description']
			];
		}
		
		$kses_allowed_html = [
			'em'     => [],
			'strong' => [],
			'small' => [],
			'big' => [],
		];
		
		foreach($new_images as $slug => $img){
			list($url, $width, $height, $name, $description) = $img;
			$path = str_replace(get_home_url(), $_SERVER['DOCUMENT_ROOT'], $url);
			?>
			<div id="anchor_<?php echo esc_attr($slug) ?>" style="margin-bottom:50px;">
				<h4><?php echo esc_html($name).' <small>('.esc_html($slug).')</small>'?></h4>
				<?php if ($description): ?>
					<p class="description"><?php echo wp_kses($description, $kses_allowed_html) ?></p>
				<?php endif; ?>
				<p><img src="<?php echo esc_url_raw($url) ?>" width="<?php echo esc_attr($width) ?>" height="<?php echo esc_attr($height) ?>" style="max-width:100%; height:auto;" alt="<?php echo esc_attr($name) ?>"></p>
				<?php if ('full' != $slug): ?>
					<p>
						<label for="ps_image_<?php echo esc_attr($slug) ?>"><?php echo esc_html__('Replace', 'packsystem').' '.$name ?> <small><?php echo esc_html__('Size Name', 'packsystem') ?>: (<?php echo esc_html("{$width}x{$height}") ?>)</small></label>
						<input type="file" class="widefat" name="ps_image[<?php echo esc_attr($slug) ?>]" id="ps_image_<?php echo esc_attr($slug) ?>" accept="image/jpeg,image/x-png,image/gif">
						<input type="hidden" name="ps_original_link[<?php echo esc_attr($slug) ?>]" value="<?php echo esc_attr($path) ?>">
					</p>
				<?php endif; ?>
			</div>
			<?php
		}
	}
	
	public function get_attachment_base_url($post_id){
		$url = '';
		// Get attached file.
		if ( $file = get_post_meta( $post_id, '_wp_attached_file', true ) ) {
			// Get upload directory.
			if ( ( $uploads = wp_get_upload_dir() ) && false === $uploads['error'] ) {
				// Check that the upload base exists in the file location.
				if ( 0 === strpos( $file, $uploads['basedir'] ) ) {
					// Replace file location with url location.
					$url = str_replace( $uploads['basedir'], $uploads['baseurl'], $file );
				} elseif ( false !== strpos( $file, 'wp-content/uploads' ) ) {
					// Get the directory name relative to the basedir (back compat for pre-2.7 uploads)
					$url = trailingslashit( $uploads['baseurl'] . '/' . _wp_get_attachment_relative_path( $file ) ) . wp_basename( $file );
				} else {
					// It's a newly-uploaded file, therefore $file is relative to the basedir.
					$url = $uploads['baseurl'] . "/$file";
				}
			}
		}
		
		$filename = wp_basename( $url );
		$url = str_replace($filename, '', $url);
		return [$url, $filename];
	}
	
	public function allow_upload($post){
		if($post->post_type != 'attachment') return;
		echo ' enctype="multipart/form-data"';
	}
	
	public function meta_boxes_save($post_id){
		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
 
		// Check if not an autosave or revision.
		if(wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {return;}
		
		$metadata = wp_get_attachment_metadata($post_id);
		$filename = wp_basename( $metadata['file'] );
		$time = str_replace('/'.$filename, '', $metadata['file']);
		
		$attachment_url = sanitize_text_field($_POST['attachment_url']);
		$ps_file_image = $_FILES['ps_image'];
		$error = '';
		foreach($ps_file_image['name'] as $field => $value){
			if(!trim($value)) continue;
			$ps_original_link = esc_url_raw($_POST['ps_original_link'][$field]);
			
			//Check if file is image
			$displayable_image_types = array( IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP );
			if ( defined( 'IMAGETYPE_ICO' ) ) $displayable_image_types[] = IMAGETYPE_ICO;
			$info_file = @getimagesize( $ps_file_image['tmp_name'][$field] );
			$result = (!empty($info_file) && in_array($info_file[2], $displayable_image_types));
			if(!$result) continue;
			
			//Rename the old file
			$old_file = $ps_original_link;
			$old_file_new = $old_file.'.old';
			@ rename($old_file, $old_file_new);

			//Set the new file name
			$ext      = pathinfo( $value, PATHINFO_EXTENSION );
			$filename = pathinfo( $attachment_url, PATHINFO_FILENAME );
			
			$file = [
				'name' => "{$filename}-{$info_file[0]}x{$info_file[1]}.{$ext}",
				'type' => sanitize_mime_type($ps_file_image['type'][$field]),
				'tmp_name' => $ps_file_image['tmp_name'][$field],
				'error' => $ps_file_image['error'][$field],
				'size' => $ps_file_image['size'][$field],
			];
			
			$result = wp_handle_upload( $file, ['test_form' => false], $time);
			if($result['error']){
				$error = $result['error'];
			}else{
				$metadata['sizes'][$field] = [
					'file' => wp_basename($result['file']),
					'width' => $info_file[0],
					'height' => $info_file[1],
					'mime-type' => $result['type']
				];
				
				wp_update_attachment_metadata($post_id, $metadata);
				@unlink($old_file_new);
			}
		}
		
		if ($error) {
			add_filter('redirect_post_location', function( $location ) use ( $error ) {
				return add_query_arg( 'cri-edit-image-error', $error['error'], $location );
			});
		}
		
		return true;
	}
}

new ChangeResponsiveImages;