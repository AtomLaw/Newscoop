<?php

/**
 * @package Campsite
 */
class ImageSearch {
	var $m_isSearch;
	var $m_orderBy;
	var $m_orderDirection;
	var $m_imageOffset;
	var $m_searchString;
	var $m_imageData;
	var $m_numImagesFound;
	var $m_orderQuery;
	var $m_whereQuery;
	var $m_itemsPerPage = 10;
		
	/**
	 * This class can search for images matching specific criteria.
	 * Give the search criteria in the contructor, then call the run()
	 * function to execute the search and get an array of the images found.  
	 *
	 * @param string $p_searchString
	 *			The string to search for.
	 *
	 * @param string $p_orderBy
	 *		Which column to order the results by.
	 *		Can be one of ["description"|"photographer"|"place"|"date"|
	 *					   "inuse"|"id"|"time_create"|"last_modified"]
	 *
	 * @param string $p_orderDirection
	 *		Order by increasing or decreasing values.
	 *		Can be  ["ASC"|"DESC"]
	 *
	 * @param int $p_offset 
	 *		Return results starting from the given offset.
	 *
	 * @param int $p_itemsPerPage
	 *		The number of results to return.
	 *
	 */
	function ImageSearch($p_searchString, $p_orderBy, $p_orderDirection = 'ASC', $p_offset = 0, $p_itemsPerPage = 0) 
	{
		global $Campsite;
		$this->m_orderBy = $p_orderBy;
		$this->m_orderDirection = $p_orderDirection;
		$this->m_imageOffset = $p_offset;
		$this->m_searchString = $p_searchString;
		if ($p_itemsPerPage > 0) {
			$this->m_itemsPerPage = $p_itemsPerPage;
		}

		// "Search by" sql
		$this->m_whereQuery = '';
		if (!empty($this->m_searchString)) {
			$this->m_whereQuery .= "WHERE Images.Description LIKE '%$this->m_searchString%'"
								. " OR Images.Photographer LIKE '%$this->m_searchString%'"
								. " OR Images.Place LIKE '%$this->m_searchString%'"
								. " OR Images.Date LIKE '%$this->m_searchString%'"
								. " OR Images.UploadedByUser LIKE '%$this->m_searchString%'";
		}
		
		// "Order by" sql
		switch ($this->m_orderBy) {
		case 'description':
			$this->m_orderQuery .= 'ORDER BY Images.Description ';
			break;
		case 'photographer':
			$this->m_orderQuery = 'ORDER BY Images.Photographer ';
			break;
		case 'place':
			$this->m_orderQuery = 'ORDER BY Images.Place ';
			break;
		case 'date':
			$this->m_orderQuery = 'ORDER BY Images.Date ';
			break;
		case 'inuse':
			$this->m_orderQuery = 'ORDER BY inUse ';
			break;
		case 'time_created':
			$this->m_orderQuery = 'ORDER BY TimeCreated ';
			$this->m_orderDirection = 'DESC';
			break;
		case 'last_modified':
			$this->m_orderQuery = 'ORDER BY LastModified ';
			$this->m_orderDirection = 'DESC';
			break;
		case 'id':
		default:
			$this->m_orderQuery = 'ORDER BY Images.Id ';
			break;
		}
		if (!empty($this->m_orderQuery)) {
			$this->m_orderQuery .= ' '.$this->m_orderDirection;
		}
	} // constructor
	
	
	/**
	 * Execute the search and return the results.
	 *
	 * @return array
	 *		An array of Image objects.
	 */
	function run() 
	{
		global $Campsite;
		$tmpImage =& new Image();
		$columnNames = $tmpImage->getColumnNames(true);
		$columnNames = implode(',', $columnNames);
		$queryStr = 'SELECT '.$columnNames.', COUNT(ArticleImages.IdImage) AS inUse'
				  	.' FROM Images '
				  	.' LEFT JOIN ArticleImages On Images.Id=ArticleImages.IdImage'
				  	.' '.$this->m_whereQuery
				    .' GROUP BY Images.Id'
				    ." $this->m_orderQuery LIMIT $this->m_imageOffset, ".$this->m_itemsPerPage;
		$numImagesFoundQueryStr = 'SELECT COUNT(DISTINCT(Images.Id))'
				  	.' FROM Images '
				  	.' LEFT JOIN ArticleImages On Images.Id=ArticleImages.IdImage'
				  	." $this->m_whereQuery";
		$rows = $Campsite['db']->GetAll($queryStr);
		$this->m_numImagesFound = $Campsite['db']->GetOne($numImagesFoundQueryStr);

		$this->m_imageData = array();
		if (is_array($rows)) {
			// Get "In Use" information
			$imageIds = array();
			foreach ($rows as $row) {
				$imageIds[$row['Id']] = '(IdImage='.$row['Id'].')';
			}
			$inUseQuery = "SELECT ArticleImages.IdImage, COUNT(Articles.Number) as in_use "
							." FROM Articles, ArticleImages "
							." WHERE (Articles.Number=ArticleImages.NrArticle) " 
							." AND (".implode(' OR ', $imageIds).")"
							." GROUP By ArticleImages.IdImage";
			$tmpInUseArray = $Campsite['db']->GetAll($inUseQuery);
			$inUseArray = array();
			// Make it an associative array for easy lookup in the next loop.
			if (is_array($tmpInUseArray)) {
				foreach ($tmpInUseArray as $inUseItem) {
					$inUseArray[$inUseItem['IdImage']] = $inUseItem['in_use'];
				}
			}
			// Create image templates
			foreach ($rows as $row) {
				$tmpImage =& new Image();
				$tmpImage->fetch($row);
				$template = $tmpImage->toTemplate();
				$template['in_use'] = 0;
				if (isset($inUseArray[$row['Id']])) {
					$template['in_use'] = $inUseArray[$row['Id']];
				}
				$imageSize = getimagesize($tmpImage->getImageStorageLocation());
				$template['width'] = $imageSize[0];
				$template['height'] = $imageSize[1];
				$this->m_imageData[] = $template;
			}
		}
		return $this->m_imageData;
	} // fn run
	
	
	/**
	 * Return the images that were found.
	 * @return array
	 */
	function getImages() 
	{
		return $this->m_imageData;
	} // fn getImages
	
	
	/**
	 * Return the total number of images that match the search.
	 * Note that this may be different than the total number of 
	 * images returned by run() or getImages() because that array
	 * is limited to the set "images per page".  The number returned
	 * by this function is the total number of images without that 
	 * restriction.
	 *
	 * @return int
	 */
	function getNumImagesFound() 
	{
		return $this->m_numImagesFound;
	} // fn getNumImagesFound
	
	
	/**
	 * The current value for the number of images shown per page.
	 * @return int
	 */
	function getImagesPerPage() 
	{
		return $this->m_itemsPerPage;
	} // fn getImagesPerPage
	
	
	/**
	 * Set the max number of images to return from the run() function.
	 *
	 * @param int $p_value
	 *
	 * @return void
	 */
	function setImagesPerPage($p_value) 
	{
		$this->m_itemsPerPage = $p_value;
	} // fn setImagesPerPage
	
} // class ImageSearch


class ImageNav {
	var $m_staticSearchLink = '';
	var $m_keywordSearchLink = '';
	var $m_previousLink = '';
	var $m_nextLink = '';
	var $m_orderByLink = '';
	var $m_itemsPerPage = 10;
	var $m_view = 'thumbnail';
	var $m_searchString = '';
	var $m_orderBy = '';
	var $m_orderDirection = '';
	var $m_offset = 0;
	
	/**
	 * This class is used to create links, which will be parsed by the ImageSearch class.
	 * @param string $p_searchString
	 * @param string $p_orderBy
	 * @param string $p_orderDirection
	 * @param int $p_offset
	 * @param int $p_itemsPerPage
	 *		Number of images to show on a page.
	 * @param string $p_view
	 *		Can be: ["thumbnail"|"gallery"|"flat"].  The type of display.
	 */
	function ImageNav($p_searchString, $p_orderBy, $p_orderDirection = 'ASC', $p_offset = 0, $p_itemsPerPage = 0, $p_view = "thumbnail") 
	{
		if ($p_itemsPerPage > 0) {
			$this->m_itemsPerPage = $p_itemsPerPage;
		}
		$this->m_view = $p_view;
		$this->m_searchString = $p_searchString;
		$this->m_orderBy = $p_orderBy;
		$this->m_orderDirection = $p_orderDirection;
		$this->m_offset = $p_offset;
		$this->__buildLinks();		
	} // constructor

	
	/**
	 * Change a property of the links and rebuild the links.
	 * @param string $p_name
	 * @param string $p_value
	 * @return void
	 */
//	function setProperty($p_name, $p_value) 
//	{
//		$this->m_input[$p_name] = $p_value;
//		$this->__buildLinks();	
//	} // fn setProperty
	
	
	function clearSearchStrings() 
	{
		$this->m_searchString = '';
		$this->__buildLinks();
	} // fn clearSearchStrings
	
	
	/**
	 * Build the links based on the input.
	 * @return void
	 */
	function __buildLinks() 
	{
		$this->m_staticSearchLink = '';
		$this->m_keywordSearchLink = '';
		$this->m_orderByLink = '';
		$this->m_previousLink = '';
		$this->m_nextLink = '';		
    	$keywordSearch = false;
   		if (!empty($this->m_searchString)) {
			$this->m_keywordSearchLink .= '&f_search_string='.urlencode($this->m_searchString);
    	}

		// Build the order statement.
		if (!empty($this->m_orderBy)) {
			$this->m_orderByLink .= '&f_order_by='.$this->m_orderBy;
		}
		if (!empty($this->m_orderDirection)) {
			$this->m_orderByLink .= '&f_order_direction='.$this->m_orderDirection;
		}
		if ($this->m_offset < 0) {
			$this->m_offset = 0;
		}
		
		// Prev/Next switch
		$this->m_previousLink = 'f_image_offset='.($this->m_offset - $this->m_itemsPerPage) 
			.$this->m_keywordSearchLink.$this->m_orderByLink.'&f_view='.$this->m_view;
		$this->m_nextLink = 'f_image_offset='.($this->m_offset + $this->m_itemsPerPage)
			.$this->m_keywordSearchLink.$this->m_orderByLink.'&f_view='.$this->m_view;
		$this->m_keywordSearchLink .= '&f_image_offset='.$this->m_offset.'&f_view='.$this->m_view;
		$this->m_staticSearchLink = $this->m_keywordSearchLink . $this->m_orderByLink;		
	} // fn __buildLinks
	
	
	/**
	 * @return string
	 */
	function getKeywordSearchLink() 
	{
		return $this->m_keywordSearchLink;
	} // fn getKeywordSearchLink
	
	
	/**
	 * @return string
	 */
	function getPreviousLink() 
	{
		return $this->m_previousLink;
	} // fn getPreviousLink
	
	
	/**
	 * @return string
	 */
	function getNextLink() 
	{
		return $this->m_nextLink;
	} // fn getNextLink
	
	
	/**
	 * Produces a link to reproduce the same search.
	 * @return string
	 */
	function getSearchLink() 
	{
		return $this->m_staticSearchLink;
	} // fn getSearchLink
		
} // class ImageNav

?>