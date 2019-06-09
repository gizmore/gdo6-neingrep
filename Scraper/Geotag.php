<?php
namespace GDO\NeinGrep\Scraper;

use GDO\NeinGrep\NG_Post;
use GDO\NeinGrep\Scraper;
use GDO\Core\Logger;
use GDO\Date\Time;
use GDO\Net\HTTP;

/**
 * Scrape the geotag of images to reveal user location.
 * Does not work.
 * @author gizmore
 */
final class Geotag extends Scraper
{
	public function scrapeGeotag(NG_Post $post)
	{
		Logger::logCron("Scraping geotag {$post->getPostID()} {$post->getTitle()} for {$post->getUser()->getName()}");
		$this->scrapeGeotagB($post);
		$post->saveVar('ngp_image_scanned', Time::getDate());
	}
	
	public function scrapeGeotagB(NG_Post $post)
	{
		$url = $post->hrefImage();
		$this->beforeRequest();
		$imagedata = HTTP::getFromURL($url);
		$filename = GDO_PATH . 'temp/ng_geotag.jpg';
		file_put_contents($filename, $imagedata);
		if ($exif = @read_exif_data($filename))
		{
			$lat = $this->getGps(@$exif["GPSLatitude"], @$exif['GPSLatitudeRef']);
			$lon = $this->getGps(@$exif["GPSLongitude"], @$exif['GPSLongitudeRef']);
			if ($lat && $lon)
			{
				$post->saveVars(array(
					'ngp_position_lat' => $lat,
					'ngp_position_lng' => $lon,
				));
				Logger::logCron("Exif geotag analyzed for {$post->getUser()->getName()}: $lat/$lon.");
			}
		}
		unlink($filename);
	}
	
	function getGps($exifCoord, $hemi)
	{
		if ($exifCoord)
		{
			$degrees = count($exifCoord) > 0 ? $this->gps2Num($exifCoord[0]) : 0;
			$minutes = count($exifCoord) > 1 ? $this->gps2Num($exifCoord[1]) : 0;
			$seconds = count($exifCoord) > 2 ? $this->gps2Num($exifCoord[2]) : 0;
		
			$flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;
		
			return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
		}
	}
	
	function gps2Num($coordPart)
	{
		$parts = explode('/', $coordPart);
	
		if (count($parts) <= 0)
		{
			return 0;
		}
	
		if (count($parts) == 1)
		{
			return $parts[0];
		}
	
		return floatval($parts[0]) / floatval($parts[1]);
	}

}
