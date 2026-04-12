<?php

declare(strict_types=1);

namespace CMSImports;

/**
 * Base class for the importer plugins
 */
abstract class CMSImporterPlugin
{
    /**
     * Makes the data import: parses data from $p_inputFileName, and uses p_newsmlHolder for the formatting
     *
     * @param NewsMLCreator $p_newsmlHolder the NewsML formatter
     * @param string $p_inputFileName input file name
     * @return bool
     */
    public function makeImport(NewsMLCreator $p_newsmlHolder, string $p_inputFileName): bool
    {
        return false;
    }
}


/**
 * Holder of data of one newsItem
 */
class NewsMLNewsItem {
    // Note that we have some data as cdata, and embedded cdata shall be included correctly, see link
    // http://stackoverflow.com/questions/223652/is-there-a-way-to-escape-a-cdata-end-token-in-xml

    private $asset_item = false;
    private $asset_item_type = "";
    private $asset_item_rank = 0;

    private $content_texts = array();
    private $content_images = array();
    // private $content_videos = array();
    private $image_links = array();

    // the data that are required for newsml
    private $required_data = array(
        "unique_name" => null,
        "copyright_info" => null,
        "date_created" => null,
        "creator_literal" => null,
        "creator_name" => null,
        "slug_line" => null,
        "head_line" => null,
        "item_link" => null,
        "content" => null,
    );

    private $other_data = array(
        "title" => null,
    );

    // specifiers like categories, tags, ...
    private $subjects = array();

    // setter methods

    /**
     * Setting the item to be an asset of another item
     * @param string $p_assetType type of the asset
     * @return bool
     */
    public function becomeAsset($p_assetType, $p_assetRank) {
        if (!empty($this->image_links)) {
            return false; // the assets here are just simple items; use grouping for more complex connections
        }

        $known_asset_types = array("image" => "picture", "picture" => "picture");
        if (!array_key_exists($p_assetType, $known_asset_types)) {
            return false;
        }
        if (!is_numeric($p_assetRank)) {
            return false;
        }
        $p_assetRank = intval($p_assetRank);
        if (0 >= $p_assetRank) {
            return false;
        }

        $this->asset_item = true;
        $this->asset_item_type = $known_asset_types[$p_assetType];
        $this->asset_item_rank = $p_assetRank;

        return true;
    }

    /**
     * Setting the unique name
     * @param string $p_uniqueName name for guid creation
     * @return bool
     */
    public function setUniqueName($p_uniqueName) {
        //if ($this->asset_item) {
        //    return false;
        //}
        $this->required_data["unique_name"] = str_replace(array("\"", ":", "$"), array("&#34;", "&#58;", "&#36;"), $p_uniqueName); // shall not escape "...", and w/o ':', '/';
        return true;
    }

    /**
     * Setting the copyright data
     * @param string $p_copyrightInfo copyright holder
     * @return bool
     */
    public function setCopyright($p_copyrightInfo) {
        $this->required_data["copyright_info"] = str_replace(array("\""), array("&#34;"), $p_copyrightInfo); // shall not escape "..."
        return true;
    } // fn setCopyright

    /**
     * Setting the date-time data
     * @param string $p_date
     * @param string $p_time
     * @param string $p_zone
     * @return bool
     *
     * the xml date-time format is according to the link
     * http://www.w3schools.com/Schema/schema_dtypes_date.asp
     */
    public function setCreated($p_date = null, $p_time = null, $p_zone = null) {
        $date_time = "";

        if ($p_date) {
            $p_date = (string) $p_date;
            if (!preg_match("/^[\d]{4}-[\d]{2}-[\d]{2}$/", $p_date)) {
                return false;
            }
        }
        if ($p_time) {
            $p_time = (string) $p_time;
            if (!preg_match("/^[\d]{2}(:[\d]{2}(:[\d]{2}(\.[\d]+)?)?)?$/", $p_time)) {
                return false;
            }
        }
        if ($p_zone) {
            $p_zone = (string) $p_zone;
            $zone_correct = false;
            if ("Z" == $p_zone) {
                $zone_correct = true;
            }
            if (preg_match("/^[+-]{1}[\d]{2}(:[\d]{2})?$/", $p_zone)) {
                $zone_correct = true;
            }
            if (!$zone_correct) {
                return false;
            }
        }

        if (!$p_date) {
            $date_time = gmdate("Y-m-d\TH:i:s\Z");
        }
        elseif (!$p_time) {
            $date_time = $p_date . "T00:00:00Z";
        }
        elseif (!$p_zone) {
            $date_time = $p_date . "T" . $p_time . "Z";
        }
        else {
            $date_time = $p_date . "T" . $p_time . $p_zone;
        }

        $this->required_data["date_created"] = $date_time;

        return true;
    } // fn setCreated

    /**
     * Setting the creator data
     * @param string $p_literal
     * @param string $p_name
     * @return bool
     */
    public function setCreator($p_literal, $p_name) {
        $this->required_data["creator_literal"] = str_replace(array("\""), array("&#34;"), $p_literal); // shall not escape "..."
        //$this->required_data["creator_name"] = str_replace(array("[", "]"), array("&#91;", "&#93;"), $p_name); // shall not have cdata
        $this->required_data["creator_name"] = str_replace(array("]]>"), array("]]]]><![CDATA[>"), $p_name); // shall not have cdata
        return true;
    } // fn setCreator

    /**
     * Setting the slugline
     * @param string $p_slugline
     * @return bool
     */
    public function setSlugline($p_slugLine) {
        //$this->required_data["slug_line"] = str_replace(array("&", "<", ">", "\""), array("&amp;", "&lt;", "&gt;", "&#34;"), $p_slugLine); // shall not have <tag>, shall not escape "..."
        $this->required_data["slug_line"] = str_replace(array("]]>"), array("]]]]><![CDATA[>"), $p_slugLine); // shall not have cdata seps
        return true;
    } // fn setSlugline

    /**
     * Setting the headline
     * @param string $p_headline
     * @return bool
     */
    public function setHeadline($p_headLine) {
        //$this->required_data["head_line"] = str_replace(array("[", "]"), array("&#91;", "&#93;"), $p_headLine); // shall not have cdata seps
        $this->required_data["head_line"] = str_replace(array("]]>"), array("]]]]><![CDATA[>"), $p_headLine); // shall not have cdata seps
        return true;
    } // fn setHeadline

    /**
     * Setting the link
     * @param string $p_link
     * @return bool
     */
    public function setLink($p_linkUrl) {
        $this->required_data["item_link"] = str_replace(array("\""), array("&#34;"), $p_linkUrl); // shall not escape "..."
        return true;
    } // fn setLink

    /**
     * Setting the link to an image
     * @param string $p_imageRank
     * @return bool
     */
    public function setImageLink($p_imageRank) {
        if ($this->asset_item) {
            return false; // the assets here are just simple items; use grouping for more complex connections
        }

        if (!is_numeric($p_imageRank)) {
            return false;
        }
        $p_imageRank = intval($p_imageRank);
        if (0 >= $p_imageRank) {
            return false;
        }
        //$this->image_links[] = str_replace(array("\""), array("&#34;"), $p_imageRel); // shall not escape "..."
        $this->image_links[] = $p_imageRank;
        return true;
    } // fn setImageLink

    /**
     * Setting the article content by itself
     * @param string $p_text
     * @return bool
     */
    public function setContent($p_type, $p_content) {

        $content_array = null;
        $cont_used = false;

        if (in_array($p_type, array("text", "texts"))) {
            $content_array = &$this->content_texts;

            if (!is_array($p_content)) {
                $p_content = array($p_content);
            }
            foreach ($p_content as $one_text) {
                $content_array[] = str_replace(array("]]>"), array("]]]]><![CDATA[>"), (string) $one_text); // shall not have cdata seps
                $this->required_data["content"] = true;
                $cont_used = true;
            }
        }

        if (in_array($p_type, array("image", "images", "picture", "pictures"))) {

            $content_array = &$this->content_images;

            if (!is_array($p_content)) {
                return false;
            }
            if (empty($p_content)) {
                return false;
            }
            $single_image = false;
            if (array_key_exists("href", $p_content)) {
                $single_image = true;
            }
            if ($single_image) {
                $p_content = array($p_content);
            }

            foreach ($p_content as $one_image) {

                if (!is_array($one_image)) {
                    continue;
                }
                if (!array_key_exists("href", $one_image)) {
                    continue;
                }

                $img_href = str_replace(array("\""), array("&#34;"), (string) $one_image["href"]); // shall not escape "...", some w/o ':' too

                $img_width = (array_key_exists("width", $one_image)) ? (0 + (int) $one_image["width"]) : 0;
                $img_height = (array_key_exists("height", $one_image)) ? (0 + (int) $one_image["height"]) : 0;
                $img_size = (array_key_exists("size", $one_image)) ? (0 + (int) $one_image["size"]) : 0;
                $img_type = (array_key_exists("type", $one_image)) ? str_replace(array("\""), array("&#34;"), (string) $one_image["type"]) : "image/*";
                $img_colors = (array_key_exists("colors", $one_image)) ? str_replace(array("\"", ":"), array("&#34;", "&#58;"), (string) $one_image["colors"]) : "AdobeRGB";
                $img_class = (array_key_exists("class", $one_image)) ? str_replace(array("\"", ":"), array("&#34;", "&#58;"), (string) $one_image["class"]) : "web";
                $img_version = (array_key_exists("version", $one_image)) ? (0 + (int) $one_image["version"]) : 0;

                $use_image = array(
                    "href" => $img_href,
                    "width" => $img_width,
                    "height" => $img_height,
                    "size" => $img_size,
                    "type" => $img_type,
                    "colors" => $img_colors,
                    "class" => $img_class,
                    "version" => $img_version,
                );
                $content_array[] = $use_image;

                $this->required_data["content"] = true;
                $cont_used = true;

            }
        }

        return $cont_used;
    } // fn setContent

    /**
     * Setting the article specifiers
     * @param string $p_qcode
     * @param string $p_name
     * @return bool
     */
    public function setSubject($p_qcode, $p_name) {
        //$this->subjects[] = array("qcode" => str_replace(array("\"", ":", "/"), array("&#34;", "&#58;", "&#47;"), $p_qcode), // shall not escape "...", and w/o ':', '/';
        $this->subjects[] = array("qcode" => str_replace(array("\""), array("&#34;"), $p_qcode), // shall not escape "..."
                                  //"name" => str_replace(array("[", "]"), array("&#91;", "&#93;"), $p_name), // shall not have cdata seps
                                  "name" => str_replace(array("]]>"), array("]]]]><![CDATA[>"), $p_name), // shall not have cdata seps
                                 );
        return true;
    } // fn setSubject

    /**
     * Setting the item title, used e.g. for images
     * @return bool
     */
    public function setTitle($p_title) {
        $this->other_data["title"] = str_replace(array("]]>"), array("]]]]><![CDATA[>"), (string) $p_title);
        return true;
    }

    // checker methods

    /**
     * Checks whether all required data are set
     * @return bool
     */
    public function isFilled() {
        foreach ($this->required_data as $one_key => $one_info) {
            if (is_null($one_info)) {
                return false;
            }
        }

        return true;

    } // fn isFilled

    // getter methods

    /**
     * Gets the name used for guid
     * @return string
     */
    public function getUniqueName() {
        $uname = (string) $this->required_data["unique_name"];
        if ($this->asset_item) {
            $uname .= "$" . $this->asset_item_type . "-" . $this->asset_item_rank;
        }

        return $uname;
    }

    /**
     * Gets item date
     * @return string
     */
    public function getDate() {
        return (string) $this->required_data["date_created"];
    } // fn getDate

    /**
     * Gets item headline
     * @return string
     */
    public function getHeadline() {
        return (string) $this->required_data["head_line"];
    } // fn getHeadline

    /**
     * Gets item slugline
     * @return string
     */
    public function getSlugline() {
        return (string) $this->required_data["slug_line"];
    } // fn getSlugline

    /**
     * Gets item copyright
     * @return string
     */
    public function getCopyright() {
        return (string) $this->required_data["copyright_info"];
    } // fn getCopyright

    /**
     * Gets item creator
     * @return string
     */
    public function getCreator($p_part) {
        switch ($p_part) {
            case "literal":
                return (string) $this->required_data["creator_literal"];
            case "name":
                return (string) $this->required_data["creator_name"];
            default:
                return "";
        }
    } // fn getCreator

    /**
     * Gets item link
     * @return string
     */
    public function getLink() {
        return (string) $this->required_data["item_link"];
    } // getLink

    /**
     * Gets item links
     * @return array
     */
    public function getLinks() {
        if ($this->asset_item) {
            return array();
        }

        $links = array();
        $common_itemrel = "urn:newsml:sourcefabric.org:" . $this->getDate() . ":" . $this->getUniqueName();
        foreach ($this->image_links as $one_image_rank) {
            $one_itemrel = $common_itemrel . '$image-' . $one_image_rank;
            $links[] = array("itemrel_relation" => 'dependsOn', "itemrel_type" => 'residref', "itemrel_value" => (string) $one_itemrel);
        }
        return $links;
    } // getLinks

    /**
     * Gets item subjects (array of categories, tags, ...)
     * @return array
     */
    public function getSubjects() {
        return $this->subjects;
    } // fn getSubjects

    /**
     * Gets item payload type
     * @return string
     */
    public function getContentType() {
        $has_texts = count($this->content_texts) ? 1 : 0;
        $has_images = count($this->content_images) ? 1 : 0;
        //$has_videos = count($this->content_videos) ? 1 : 0;

        //$has_contents = $has_texts + $has_images + $has_videos;
        $has_contents = $has_texts + $has_images;
        if (1 < $has_contents) {
            return "composite";
        }
        if ((0 == $has_contents) || $has_texts) {
            return "text";
        }
        if ($has_images) {
            return "picture";
        }
        //if ($has_videos) {
        //    return "video";
        //}

        return "text";
    }

    /**
     * Gets item content
     * @return mixed
     */
    public function getContent($p_type) {
        if (in_array($p_type, array("text", "texts"))) {
            return $this->content_texts;
        }
        //return $this->required_data["content"];

        if (in_array($p_type, array("image", "images", "picture", "pictures"))) {
            return $this->content_images;
        }

        return null;
    } // fn getContent

    /**
     * Gets item title
     * @return string
     */
    public function getTitle() {
        return (string) $this->other_data["title"];
    }

} // class NewsMLNewsItem


/**
 * Holder and formatter of all the creted NewsML data (i.e. of newsMessage)
 */
class NewsMLCreator {

    private $state_correct = true;
    private $error_message = "";

    // for the header, id, itemRef-list
    private $overall_info;

    // for the created news items
    private $itemSet = array();

    // for the result output holder
    private $outputName = "";

    /**
     * Constructor
     *
     * @param string p_outputFileName output file name
     */
    public function __construct($p_outputFileName) {
        $this->outputName = $p_outputFileName;
    } // fn NewsMLCreator

    /**
     * Sets an error state
     *
     * @param string $p_message
     * @return void
     */
    public function setError($p_message) {
        $this->state_correct = false;
        $this->error_message = $p_message;
    } // fn setError

    /**
     * Creates a new newsItem
     *
     * @return mixed
     */
    public function createItem() {
        return new NewsMLNewsItem();
    } // fn createItem

    /**
     * Creates a new newsItem
     *
     * @return mixed
     */
/*
    public function createItemAsset($p_assetType) {
        $known_asset_types = array("image", "picture");
        if (!in_array($p_assetType, $known_asset_types)) {
            return null;
        }

        return new NewsMLNewsItem();
    } // fn createItem
*/

    /**
     * Adds a filled newsItem to the itemSet
     *
     * @param NewsMLNewsItem $p_newsItem a filled newsItem
     * @return bool
     */
    public function appendItem(NewsMLNewsItem $p_newsItem) {
        if (!$p_newsItem->isFilled()) {
            return false;
        }

        $this->itemSet[] = $p_newsItem;

        return true;
    } // fn appendItem

    /**
     * Final operation for outputting
     *
     * @return bool
     */
    public function serializeSet() {
        $out_file = fopen($this->outputName, "w");
        if (!$out_file) {
            return false;
        }

        if (!$this->state_correct) {
            fwrite($out_file, $this->error_message);
            fclose($out_file);
            return true;
        }

        $curdate = gmdate("Y-m-d\TH:i:s\Z");

        $newsml_content = '';

        // newsMessage beginning according to Ch16, IPTC-G2-Implementation_Guide
        $newsml_content .= '<?xml version="1.0" encoding="UTF-8" ?>
<newsMessage xmlns="http://iptc.org/std/nar/2006-10-01/"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://iptc.org/std/nar/2006-10-01/
        ../XSD/NewsML-G2/2.7/specification/NAR_1.8-spec-NewsMessage-Power.xsd">
    <header>
        <sent>' . $curdate . '</sent>
        <sender>sourcefabric.org</sender>
        <transmitId>sourcefabric.org:' . $curdate . '</transmitId>
    </header>
    <itemSet>
        <packageItem>
';

        foreach ($this->itemSet as $one_item) {
            $newsml_content .= '            <itemRef residref="urn:newsml:sourcefabric.org:' . $one_item->getDate() . ':' . $one_item->getUniqueName() . '" />' . "\n";
        }

        $newsml_content .= '        </packageItem>';

        foreach ($this->itemSet as $one_item) {
            $newsml_content .= '
        <newsItem guid="urn:newsml:sourcefabric.org:' . $one_item->getDate() . ':' . $one_item->getUniqueName() . '"
            xmlns="http://iptc.org/std/nar/2006-10-01/"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://iptc.org/std/nar/2006-10-01/
                ../specification/NAR_1.8-spec-All-Core.xsd"
            standard="NewsML-G2"
            standardversion="2.7"
            version="1"
            xml:lang="en-US"
            >
            <catalogRef href="http://www.iptc.org/std/catalog/catalog.IPTC-G2-Standards_13.xml" />
            <catalogRef href="http://www.sourcefabric.org/odis/catalogs/nmlcodes.xml" />
            <rightsInfo>
                <copyrightHolder literal="' . $one_item->getCopyright() . '" />
            </rightsInfo>
            <itemMeta>
                <itemClass qcode="ninat:' . $one_item->getContentType() . '" />
                <provider literal="Newscoop Online Data Import Service" />
                <versionCreated>' . $one_item->getDate() . '</versionCreated>
                <pubStatus qcode="stat:usable" />
                <service qcode="svc:WPI">
                    <name>Word Press Import</name>
                </service>';

            $one_title = $one_item->getTitle();
            if (0 < strlen($one_title)) {
                $newsml_content .= '
                <title><![CDATA[' . $one_title . ']]></title>';
            }

            $newsml_content .= '
            </itemMeta>
            <contentMeta>
                <contentCreated>' . $one_item->getDate() . '</contentCreated>
                <contentModified></contentModified>
                <creator literal="' . $one_item->getCreator("literal") . '">
                    <name><![CDATA[' . $one_item->getCreator("name") . ']]></name>
                </creator>
                <language tag="en-US" />';
            foreach ($one_item->getSubjects() as $one_subjects) {
                $newsml_content .= '
                <subject qcode="' . $one_subjects["qcode"] . '">
                    <name><![CDATA[' . $one_subjects["name"] . ']]></name>
                </subject>';
                }
            $newsml_content .= '
                <link rel="itemrel:original" href="' . $one_item->getLink() . '" />';

            foreach ($one_item->getLinks() as $one_item_link) {
                $one_item_rel_relation = $one_item_link["itemrel_relation"]; // e.g. original, dependson, picture, ...
                $one_item_rel_type = $one_item_link["itemrel_type"]; // e.g. href, residref, ...
                $one_item_rel_value = $one_item_link["itemrel_value"]; // e.g. http://..., urn:newsml:sourcefabric.org:..., ...
                $newsml_content .= '
                <link rel="itemrel:' . $one_item_rel_relation . '" ' . $one_item_rel_type . '="' . $one_item_rel_value . '" />';
                }

            $newsml_content .= '
                <slugline><![CDATA[' . $one_item->getSlugline() . ']]></slugline>
                <headline><![CDATA[' . $one_item->getHeadline() . ']]></headline>
            </contentMeta>
            <contentSet>';
        $text_array = $one_item->getContent("text");
        $image_array = $one_item->getContent("picture");
        if (empty($text_array) && (empty($image_array))) {
            $newsml_content .= '
                <inlineXML contenttype="application/xhtml+xml; charset=UTF-8">
<![CDATA[]]>
                </inlineXML>';
        }
        foreach ($text_array as $one_text) {
            $newsml_content .= '
                <inlineXML contenttype="application/xhtml+xml; charset=UTF-8">
<![CDATA[
' . $one_text . '
]]>
                </inlineXML>';
        }
        foreach ($image_array as $one_image) {
            $img_url = $one_image["href"];
            $img_width = $one_image["width"];
            $img_height = $one_image["height"];
            $img_size = $one_image["size"];
            $img_type = $one_image["type"];
            $img_colors = $one_image["colors"];
            $img_class = $one_image["class"];
            $img_version = $one_image["version"];
            $newsml_content .= '
                <remoteContent
                        href="' . $img_url . '"
                        rendition="rnd:' . $img_class . '"
                        size="' . $img_size . '"
                        contenttype="' . $img_type . '"
                        width="' . $img_width . '"
                        height="' . $img_height . '"
                        version="' . $img_version . '"
                        colourspace="colsp:' . $img_colors . '" />';
        }
        $newsml_content .= '
            </contentSet>
        </newsItem>';

        }

        $newsml_content .= '
    </itemSet>
</newsMessage>
';

        fwrite($out_file, $newsml_content);
        fclose($out_file);

        return false;
    } // fn serializeSet

} // class NewsMLCreator


?>
