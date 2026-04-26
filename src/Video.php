<?php
namespace ProjectSoft;

class Video {

	/** Ссылка на ролик */
	private $link;
	
	/** Evolution CMS */
	private $modx;
	
	/** Видеохостинг */
	private $hosting;

	/** данные видео */
	private $videoInfo = array();

	/** Автоматическое сохранение изображения */
	private $autosave = false;

	/** Ссылка на каталог перевьюшек */
	private $dir_images = "assets/images/video/";

	const YOUTUBE = 'youtube';
	const RUTUBE  = 'rutube';
	const VK      = 'vkvideo';
	const DEF     = 'default';

	/**
	 * @param string|null $link ссылка на видео
	 */
	public function __construct(string $link = null, bool $autosave = false, array &$videoInfo = array())
	{
		$this->modx = evo();
		$this->autosave = $autosave ? true : false;
		if(!is_null($link) || !empty($link)):
			$videoInfo = $this->setLink($link);
		endif;
	}

	public function setLink(string $link = null)
	{
		$this->videoInfo = array();
		if(!empty($link)):
			$this->videoInfo = $this->cleanLink($link)->getVideoInfo();
		endif;
		return $this->videoInfo;
	}

	private function log($data, $line=__LINE__)
	{
		file_put_contents(dirname(__FILE__) . '/videoInfo.txt', $line . PHP_EOL . print_r($data, true) . PHP_EOL, FILE_APPEND);
	}

	private function errorFn($data, $line=__LINE__)
	{
		$this->videoInfo["error"][] = trim(print_r($data, true)) . PHP_EOL . "(" . $line . ")" . PHP_EOL . PHP_EOL;
	}

	/** Проверка и подготовка ссылки и частей */
	private function cleanLink($link)
	{
		if (!preg_match('/^(http|https)\:\/\//i', $link)):
			$this->link = $link;
		else:
			// Забираем видео только по https !!!
			$this->link = preg_replace('/^(?:https?):\/\//i', 'https://', $link, 1);
		endif;
		return $this;
	}

	/** Определяем хостинг и получаем информацию о видео */
	private function getVideoInfo()
	{
		//$re_youtube = '/^(?:https?\:\/\/(?:[w]{3}\.)?)(youtu(?:\.be|be\.com))\//i';
		//$re_rutube  = '/^(?:https?\:\/\/(?:[w]{3}\.)?)(rutube\.ru)/i';
		$re = $re = '/^(?:https?:\/\/)?(?:[w]{3}\.)?([^\/]+)/i';
		preg_match($re, $this->link, $matches);
		//$this->log($matches, __LINE__);
		if(count($matches)):
			$host = mb_strtolower($matches[1]);
			switch($host) {
				// YouTube
				case 'youtu.be':
				case 'youtube.com':
					$this->hosting = self::YOUTUBE;
					return $this->getYouTubeInfo();
					break;
				// Rutube
				case 'rutube.ru':
					$this->hosting = self::RUTUBE;
					return $this->getRuTubeInfo();
					break;
				case 'vk.com':
				case 'vk.ru':
				case 'vkvideo.com':
				case 'vkvideo.ru':
					$this->hosting = self::VK;
					return $this->getVkInfo();
					break;
				default:
					if (filter_var($this->link, FILTER_VALIDATE_URL)):
						$url_info = parse_url($this->link);
						$this->hosting = $url_info['host'];
						return $this->getVideoLinkInfo();
					else:
						// Локальная ссылка
						$this->hosting = self::DEF;
						return $this->getDefaultInfo();
					endif;
					$this->errorFn('Error url: ' . $this->link, __LINE__);
					return $this->videoInfo;
					break;
			}
		else:
			// Если на сервере - вернуть getDefaultInfo
			return $this->getDefaultInfo();
		endif;
	}

	/** Получение информации с VK */
	private function getVkInfo() {
		/**
		 * videoInfo
		 *
		 * array(
		 * 	'id' => '',
		 * 	'link' => '',
		 * 	'player' => '',
		 * 	'video' => '',
		 * 	'provider' => '',
		 * 	'image' => ''
		 * )
		 *
		 */
		$this->videoInfo = array();
		$re = '/video(-?\d+_\d+)/i';
		preg_match($re, $this->link, $match);
		if(count($match)):
			$id = (string)$match[1];
			$ids = explode("_", $match[1]);
			$player = 'https://vkvideo.ru/video_ext.php?oid=' . $ids[0] . '&id=' . $ids[1] . '&js_api=1&hd=4&loop=0&t=00h00m00s';
			$this->videoInfo['id'] = $id;
			$this->videoInfo['link'] = $this->link;
			$this->videoInfo['player'] = $player;
			$this->videoInfo['video'] = '<div class="embed"><div class="embed-responsive embed-responsive-16by9"><iframe src="' . $player . '" frameborder="0" allow="clipboard-write" loading="lazy" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div></div>';
			$this->videoInfo['provider'] = $this->hosting;
			$this->videoInfo['type'] = 'iframe';
			$img_file = $this->dir_images . $this->hosting . '/' . $id . '.jpg';
			if(!is_dir(MODX_BASE_PATH . $this->dir_images . $this->hosting)):
				@mkdir(MODX_BASE_PATH . $this->dir_images . $this->hosting . '/', 0755, true);
			endif;
			$this->videoInfo['image'] = $img_file;
		else:
			$this->errorFn('Error url: ' . $this->link, __LINE__);
		endif;
		return $this->videoInfo;
	}

	/** Получение информации с RuTube */
	private function getRuTubeInfo()
	{
		/**
		 * videoInfo
		 *
		 * array(
		 * 	'id' => '',
		 * 	'link' => '',
		 * 	'player' => '',
		 * 	'video' => '',
		 * 	'provider' => '',
		 * 	'image' => ''
		 * )
		 *
		 */
		// Забираем video или shorts
		$re = '/\/(?:video|shorts)\/([\w\-_]+)/i';
		preg_match($re, $this->link, $match);
		if(count($match)):
			$id = $match[1];
			$query = http_build_query(array(
				"no_404" => "true",
				"referer" => $this->modx->config["site_url"],
				"client" => "wdp",
				"mq" => "all"
			));
			$link = "https://rutube.ru/api/play/options/" . $id . "/?" . $query;
			$img_file = $this->dir_images . $this->hosting . '/' . $id . '.jpg';

			$this->videoInfo['id'] = $id;
			$this->videoInfo['link'] = $this->link;
			$this->videoInfo['player'] = 'https://rutube.ru/play/embed/' . $id;
			$this->videoInfo['video'] = '<div class="embed"><div class="embed-responsive embed-responsive-16by9"><iframe src="' . $this->videoInfo['player'] . '" frameborder="0" allow="clipboard-write" loading="lazy" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div></div>';
			$this->videoInfo['provider'] = $this->hosting;
			$this->videoInfo['type'] = 'iframe';
			$this->videoInfo['image'] = $img_file;
			if(!is_file(MODX_BASE_PATH . $img_file)):
				// Если изображения нет, то делаем запрос за данными
				$str = $this->fetchPage($link);
				if($str):
					$json = json_decode($str, true);
					if($json['video_id']):
						/** Скачать и сохранить если сохраняется документ */
						@mkdir(MODX_BASE_PATH . $this->dir_images . $this->hosting . '/', 0755, true);
						$img_file = $this->dir_images . $this->hosting . '/' . $id . '.jpg';
						if($this->autosave):
							$img = $this->fetchPage($json['thumbnail_url']);
							if($img):
								@file_put_contents(MODX_BASE_PATH . $img_file, $img);
								if(is_file(MODX_BASE_PATH . $img_file)):
									$image = $this->modx->runSnippet('phpthumb', array(
										'input' => $img_file,
										'options' => 'w=680,h=383,zc=C'
									));
									@copy(MODX_BASE_PATH . $image, MODX_BASE_PATH . $img_file);
									$this->videoInfo['image'] = $img_file;
								endif;
							endif;
						endif;
						if(!is_file(MODX_BASE_PATH . $img_file)):
							$this->videoInfo['image'] = $json['thumbnail_url'];
						endif;
					else:
						$this->errorFn('Error url: ' . $this->link, __LINE__);
					endif;
				else:
					$this->errorFn('Error url: ' . $this->link, __LINE__);
				endif;
			endif;
		else:
			$this->errorFn('Error url: ' . $this->link, __LINE__);
		endif;
		return $this->videoInfo;
	}

	/** Получение информации с YouTube */
	private function getYouTubeInfo()
	{
		/**
		 * videoInfo
		 *
		 * array(
		 * 	'id' => '',
		 * 	'link' => '',
		 * 	'player' => '',
		 * 	'video' => '',
		 * 	'provider' => '',
		 * 	'image' => '',
		 *  'type' => ''
		 * )
		 *
		 */
		$re = '#(?<=(?:v|i)=)[a-z0-9-_]+(?=&)|(?<=(?:v|i)\/)[^&\n]+|(?<=embed\/)[^"&\n]+|(?<=(?:v|i)=)[^&\n]+|(?<=youtu.be\/)[^&\n]+#i';
		preg_match($re, $this->link, $match);
		if(count($match)):
			$this->videoInfo['id'] = $match[0];
			$this->videoInfo['link'] = $this->link;
			$embed = 'https://www.youtube.com/embed/' . $match[0] . '?';
			parse_str(parse_url($this->link, PHP_URL_QUERY), $params);
			if($params['list']):
				$embed .= 'list=' . $params['list'] . '&';
			endif;
			$embed .= 'showinfo=0&modestbranding=1&rel=0';
			$this->videoInfo['player'] = $embed;
			$this->videoInfo['video'] = '<div class="embed"><div class="embed-responsive embed-responsive-16by9"><iframe src="' . $embed . '" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture" loading="lazy" webkitAllowFullScreen mozallowfullscreen allowfullscreen></iframe></div></div>';
			$this->videoInfo['provider'] = $this->hosting;
			$this->videoInfo['type'] = 'iframe';
			/** Скачать и сохранить если сохраняется документ */
			$image = "https://img.youtube.com/vi/" . $match[0] . "/sddefault.jpg";
			@mkdir(MODX_BASE_PATH . $this->dir_images . $this->hosting . '/', 0755, true);
			$img_file = $this->dir_images . $this->hosting . '/' . $match[0] . '.jpg';
			$this->videoInfo['image'] = $img_file;
			if($this->autosave && !is_file(MODX_BASE_PATH . $img_file)):
				$img = $this->fetchPage($image);
				if($img):
					@file_put_contents(MODX_BASE_PATH . $img_file, $img);
					if(is_file(MODX_BASE_PATH . $img_file)):
						$image = $this->modx->runSnippet('phpthumb', array(
							'input' => $img_file,
							'options' => 'w=680,h=360,zc=C'
						));
						@copy(MODX_BASE_PATH . $image, MODX_BASE_PATH . $img_file);
					endif;
				endif;
			endif;
			if(!is_file(MODX_BASE_PATH . $img_file)):
				$this->videoInfo['image'] = $image;
			endif;
		else:
			$this->errorFn('Error url: ' . $this->link, __LINE__);
		endif;
		return $this->videoInfo;
	}

	// Видео на сервере сайта
	private function getDefaultInfo()
	{
		$file_info = pathinfo($this->link);
		$this->videoInfo['id'] = $file_info['filename'];
		$this->videoInfo['link'] = $this->modx->config["site_url"] . $this->link;
		$this->videoInfo['player'] = $this->modx->config["site_url"] . $this->link;
		$path = $this->dir_images . $file_info['dirname'] . '/';
		if(!is_dir(MODX_BASE_PATH . $path)):
			@mkdir(MODX_BASE_PATH . $path. '/', 0755, true);
		endif;
		$image = $path . $file_info['filename'] . '.jpg';
		$this->videoInfo['video'] = '<div class="embed"><div class="embed-responsive embed-responsive-16by9"><video src="/' . $this->link . '" crossorigin="anonymous" preload="none" poster="/' . $image . '" controls></video></div></div>';
		$this->videoInfo['provider'] = $this->hosting;
		$this->videoInfo['image'] = $image;
		$this->videoInfo['type'] = 'video';
		return $this->videoInfo;
	}

	/**
	 * Видео на другом сайте
	 * Именно на файл видео
	 */
	private function getVideoLinkInfo()
	{
		$url_info = parse_url($this->link);
		$pathinfo = pathinfo($url_info['host'] . $url_info['path']);
		$path = $pathinfo['dirname'];
		$file_name = mb_strtolower($pathinfo['filename']) . '.jpg';
		$this->videoInfo['id'] = $pathinfo['filename'];
		$this->videoInfo['link'] = $this->link;
		$this->videoInfo['player'] = $this->link;
		$img_path = $this->dir_images . $path;
		if(!is_dir(MODX_BASE_PATH . $img_path)):
			@mkdir(MODX_BASE_PATH . $img_path . '/', 0755, true);
		endif;
		$this->videoInfo['image'] = $img_path . '/' . $file_name;
		$this->videoInfo['video'] = '<div class="embed"><div class="embed-responsive embed-responsive-16by9"><video src="' . $this->link . '" crossorigin="anonymous" preload="none" poster="' . $img_path . '/' . $file_name . '" controls></video></div></div>';
		$this->videoInfo['provider'] = $url_info['host'];
		$this->videoInfo['type'] = 'video';
		return $this->videoInfo;
	}

	/** Скачивание с помощью CURL */
	private function fetchPage($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpCode >= 400):
			return false;
		endif;
		return $result;
	}
}
