<?php
namespace ProjectSoft;

class PrepareDL {

	public static function prepareItemRSS(array $data, \DocumentParser $modx, $_DL, \prepare_DL_Extender $_extDocLister)
	{
		$value1 = isset($data["meta_image"]) ? $data["meta_image"] : "";
		$image = $modx->runSnippet('phpthumb', array(
			'input' => $value1,
			'options' => 'f=jpeg,w=768,h=432,zc=C'
		));
		$data["meta_image"] = $modx->config["site_url"] . $image;
		// Description
		$value2 = isset($data["introtext"]) ? $data["introtext"] : '';
		$value2 = preg_replace('@<style.*?>.*?</style>@is', '', $value2);
		$value2 = preg_replace('@<script.*?>.*?</script>@is', '', $value2);
		$value2 = preg_replace('/[\r\n]+(?:\s+)?/', "<br>", strip_tags($value2));
		$value2 = $modx->htmlspecialchars(stripslashes( $value2 ));
		$data["introtext"] = "<![CDATA[" . $value2 . "]]>";
		// Return
		return $data;
	}

	public static function prepareItem(array $data, \DocumentParser $modx, $_DL, \prepare_DL_Extender $_extDocLister)
	{
		$month = array(
			'1' =>  'января',
			'2'	=>  'февраля',
			'3' =>  'марта',
			'4' =>  'апреля',
			'5' =>  'мая',
			'6' =>  'июня',
			'7' =>  'июля',
			'8' =>  'августа',
			'9' =>  'сентября',
			'10' => 'октября',
			'11' => 'ноября',
			'12' => 'декабря'
		);
		$data['out_date'] = "";
		$data['seo_date'] = "";
		$date = trim($data['news_date']);
		if(($date = strtotime($data['news_date']))):
			$newsdate = date("j.n.Y", $date);
			$list = explode('.', $newsdate);
			$arr = array(
				$list[0],
				$month[$list[1]],
				$list[2]
			);
			$data['out_date'] = implode(' ', $arr)." года";
			$data['seo_date'] = date('c', $date);
		else:
			$date = intval($data['news_date']);
			$newsdate = date("j.n.Y", $date);
			$list = explode('.', $newsdate);
			$arr = array(
				$list[0],
				$month[$list[1]],
				$list[2]
			);
			$data['out_date'] = implode(' ', $arr)." года";
			$data['seo_date'] = date('c', $date);
		endif;
		$data['alt'] = Util::hsc($modx, $data['pagetitle']);
		$data['meta_image'] = $modx->runSnippet('phpthumb', array(
			'input' => $data['meta_image'],
			'options' => 'w=751,h=422,f=jpeg,zc=C'
		));
		$longtitle = trim($data['longtitle']);
		if(!empty($longtitle)):
			$data['title'] = $longtitle;
		endif;
		$data['introtext'] = preg_replace('/[\r\n]+(?:\s+)?/', "<br>", strip_tags($data['introtext']));
		return $data;
	}

}
