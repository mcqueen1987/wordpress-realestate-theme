<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php if (is_singular() && pings_open(get_queried_object())) : ?>
        <link rel="pingback" href="<?php echo esc_url(get_bloginfo('pingback_url')); ?>">
    <?php endif; ?>
    <?php wp_head(); ?>
</head>
<body id="property-content">
<?php

/**
 * Class property
 */
class property
{
    /**
     * @var null|SimpleXMLElement
     */
    private $xml = null;

    /**
     * property constructor.
     */
    function __construct()
    {
        $content_post = get_post(get_the_ID());
        $this->xml = simplexml_load_string($content_post->post_content);
    }

    /**
     * the main entrance to render the page
     * @return string
     */
    public function render()
    {
        $renderSlideBanner = $this->renderSlideBanner();
        $addr = $this->renderAddress();
        $priceView = $this->renderPriceView();
        $feature = $this->renderFeature();
        $detail = $this->renderDetail();
        $detailRight = $this->renderDetailRight();
        $openHomeDetails = $this->renderOpenHomeDetails();
        $agents = $this->renderAgents();
        return "
                <div>
                    <div>
                        $renderSlideBanner
                    </div>
                    <div>
                        <div class='detail-container'>
                            <div class='s-12 m-12 flex'>
                                <div class='title-section s-12 m-9'>
                                    $addr
                                    $priceView
                                </div>
                                <div class='feature-section s-12 m-3'>
                                    $feature
                                </div>
                            </div>
                            <div class='s-12 m-12 flex'>
                                $detail
                                $detailRight
                            </div> 
                        </div>
                    </div>
                    <div class='open-home-details'>
                        $openHomeDetails
                    </div>
                    <div id='map'></div>
                    <div class='sales-agent'>
                        $agents
                    </div>
                </div>
                ";
    }

    /**
     * get geo info
     * <eField tag="GEOCODE_LATITUDE" name="Geocode Latitude">-41.28859627</eField>
     * <eField tag="GEOCODE_LONGITUDE" name="Geocode Longitude">174.80160771</eField>
     *
     * @return array
     */
    public function getGeoCode()
    {
        $geo = ['lat' => 0, 'lng' => 0];
        foreach ($this->xml->extraFields->children() as $f) {
            if ($f->attributes()->tag == 'GEOCODE_LATITUDE') {
                $geo['lat'] = $f[0];
            }
            if ($f->attributes()->tag == 'GEOCODE_LONGITUDE') {
                $geo['lng'] = $f[0];
            }
        }
        return $geo;
    }

    /**
     * render sales agent info
     *
     * @return string
     */
    private function renderAgents(){
        $agents = '';
        foreach ($this->xml->listingAgent as $child) {
            if(!$child->name){
                continue;
            }
            $url = '';
            foreach( $child->img as $img) {
                if($img->attributes()->id == 1) {
                    $url = $img->attributes()->url;
                }
            }
            $telephone = $child->xpath("//telephone[@type='mobile']")[0];
            $name = $child->name;
            $agents .= "
                         <div class='agents-item'>
                                <div class='image'>
                                   <a href=$url>
                                        <div class='agent-image-container'>
                                            <img src=$url>
                                        </div>
                                        <div class='agent-name'>$name</div>
                                    </a>
                                </div>
                                <div class='agent-contact' >
                                    <a href=$url>
                                        <span class='agent-phone-icon'>
                                            <img style='width:18px;height:auto;'src='https://www.tommys.co.nz/wp-content/themes/flatsome-child/img/phone_icon.png'>
                                        </span> 
                                        <a class='agent-phone-num text' href='tel:$telephone'>$telephone</a>
                                    </a>
                                </div>
                                <div class='agent-contact-container custom-button'>
                                    <a class='text' href=$url>CONTACT</a>
                                </div>
                         </div>
            ";
        }

        return "
                    <p class='agents-header'>Sales Agent</p>
                    <div class='agents-list'>
                         $agents
                    </div>
                ";
    }

    /**
     * render open home details & add to calender
     * sample: 09-Jun-2019 12:30pm to 1:00pm
     *
     * @return string
     */
    private function renderOpenHomeDetails(){
        $calender = '';
        foreach ($this->xml->inspectionTimes->children() as $item) {
            $oriArr = explode(" to ", $item[0]);
            $startTimeArr = date_parse($oriArr[0]);
            $endTimeArr = date_parse( explode(" ", $oriArr[0])[0] . " " . $oriArr[1]);
            $startTimeAmOrPm = $startTimeArr['hour'] > 12 ? "PM" : "AM";
            $startTime = $startTimeArr["day"] . '/' . $startTimeArr["month"] . '/' . $startTimeArr["year"] . ' ' . $startTimeArr['hour'] . $startTimeAmOrPm;
            $endTimeAmOrPm = $endTimeArr['hour'] > 12 ? "PM" : "AM";
            $endTime = $endTimeArr["day"] . '/' . $endTimeArr["month"] . '/' . $endTimeArr["year"] . ' ' . $endTimeArr['hour'] . '' . $endTimeAmOrPm;
            $calender .= "
                <div class='open-home-details-item'>
                    <div class='month-day-date-col'>
                        <div class='month'>" . date('F', mktime(0, 0, 0, $startTimeArr["month"], 10)) . " </div>
                        <div class='date'>" . $endTimeArr["day"] . " </div>
                        <div class='day'>Sunday</div>
                    </div>
                    <div class='month-day-date-col'>
                        <div class='from'>" . $startTimeArr['hour'] . ":00" . $startTimeAmOrPm. " - </div>
                        <div class='to'>" . $endTimeArr['hour'] . ":00" . $endTimeAmOrPm. " </div>
                        <div class='days'>in 4 days</div>
                    </div>
                    <div title='Add to Calendar' class='custom-button addeventatc' data-styling='none'>
                        Add to Calendar
                        <span class='arrow'>&nbsp;</span>
                        <span class='start'>$startTime</span>
                        <span class='end'>$endTime</span>
                        <span class='timezone'>Pacific/Auckland</span>
                        <span class='title'>Tommy's Real Estate | Open Homes</span>
                        <span class='description'>Open Homes for Property  8 Yemen Place Ascot Park Wellington</span>
                        <span class='location'> 8 Yemen Place Ascot Park Wellington</span>
                        <span class='organizer'>Organizer</span>
                        <span class='organizer_email'>Organizer e-mail</span>
                        <span class='all_day_event'>false</span>
                    </div>
                </div>
        ";
        }

        return "
            <p class='open-home-header'>Open Home Details</p>
            <div class='open-home-details-list'>
               $calender
            </div>
        ";
    }

    /**
     * render the slide banner
     *
     * @return string
     */
    private function renderSlideBanner()
    {
        $data = $this->getSlideBannerData();
        $liList = "";
        foreach ($data as $item) {
            $liList .= '
                <li>
                    <div class="mask"></div>
                    <img src="' . $item . '"/>
                </li>
            ';
        }
        return '
            <ul id="property-image-gallery" >
                 ' . $liList . '
            </ul>
        ';
    }

    /**
     * get slide banner data
     *
     * @return array
     */
    private function getSlideBannerData()
    {
        $imageList = [];
        foreach ($this->xml->images->img as $image) {
            if ($image['url']) {
                $imageList[] = $image['url'];
            }
        }
        return $imageList;
    }

    /**
     * render address form
     *
     * @return string
     */
    private function renderAddress()
    {
        $addrEle = $this->xml->address;
        $addr = implode([
            $addrEle->streetNumber,
            $addrEle->street,
            $addrEle->suburb,
            $addrEle->city,
            $addrEle->state,
            $addrEle->postcode,
        ], ', ');

        return "<div class='address h2'>$addr</div>";
    }

    /**
     * render price view
     *
     * @return string
     */
    private function renderPriceView()
    {
        $priceView = $this->xml->priceView;
        return "<div class='priceView'>$priceView</div>";
    }

    /**
     * render feature table
     *
     * @return string
     */
    private function renderFeature()
    {
        $featureEle = $this->xml->features;
        if(empty($featureEle)){
            return "";
        }
        $features = [];
        $id = $this->xml->uniqueID;
        foreach ($featureEle->children() as $child) {
            if ($child != 0) {
                $features[] = "<span class=\" feature-icons " . $child->getName() . "\">
                                    <span class=\"icon-value\">$child</span>
                               </span>";
            }
        }
        return "<div class='list-id h4'>List ID: $id</div><div class='features'>" . implode($features, "\n") . "</div>";
    }

    /**
     * render property detail form
     *
     * @return string
     */
    private function renderDetail()
    {
        $detail = str_replace(PHP_EOL, "<br>", $this->xml->description);
        $legal = $this->renderLegal();
        $propertyFeatures = $this->renderPropertyFeatures();
        return "<div class='description-section s-12 m-9'>
            <div class='description'>$detail</div>
             $legal
             $propertyFeatures
            </div>";
    }

    /**
     * @return string
     */
    private function renderPropertyFeatures(){
        $property = "";
        if(!empty($this->xml->category->attributes()->name)){
            $property .= "<div class='features-house-type'>" .$this->xml->category->attributes()->name ." </div>";
        }
        if(!empty($this->xml->features->bathrooms)){
            $property .= "<div class='features-bathrooms'>" . $this->xml->features->bathrooms ." bath</div>";
        }
        if(!empty($this->xml->buildingDetails->area)){
            $property .= "<div class='features-building-size'> Floor Area is " . $this->xml->buildingDetails->area ." m² </div>";
        }
        if(!empty($this->xml->features->bedrooms)){
            $property .= "<div class='features-bedrooms'>" . $this->xml->features->bedrooms ." bed</div>";
        }
        if(!empty($this->xml->landDetails->area)){
            $property .= "<div class='features-land-size'>Land is " . $this->xml->landDetails->area ." m²</div>";
        }
        if(!empty($this->xml->features->garages)){
            $property .= "<div class='features-garage'>" . $this->xml->features->garages ." Garage</div>";
        }
        return "
            <div class='tab-section-features'>
                <h5 class='tab-title-property-features'>Property Features</h5>
                <div class='tab-content'>
                    <div class='property-features'>
                        $property
                    </div>
                </div>
            </div>
        ";
    }

    /**
     * @return string
     */
    private function renderFiles()
    {
        $media = $this->xml->media;
        $lis = '';
        foreach ($media->children() as $file) {
            $url = $file->attributes()->url;
            $title = $file->attributes()->usage;
            $lis .= "<li class='unlocked-file'> <a target='_blank' href='$url'>$title</a></li>";
        }
        return "
            <div>
                <p class='download-property-heading'>DOWNLOAD PROPERTY FILES</p>
                <ul class='attachment-links'>
                    $lis
                </ul>				
            </div>
        ";
    }

    /**
     * render detail right form
     *
     * @return string
     */
    private function renderDetailRight()
    {
        $inspectionTimes = $this->xml->inspectionTimes;
        $openHomes = '';
        foreach ($inspectionTimes->children() as $timeSlot) {
            $openHomes .= "<div>$timeSlot</div>";
        }
        $files = $this->renderFiles();
        return "<div class='s-12 m-3 open-home'>
                    <div class='h4'>UPCOMING OPEN HOME</div>
                    $openHomes
                    <div class='button custom-button'>VIEW DETAILS</div>
                    <div class='button custom-button'>CONTACT AGENT</div>
                    $files
                 </div>";
    }

    /**
     * render legal info
     *
     * @return string
     */
    private function renderLegal()
    {
        $extra = $this->xml->extraFields;
        if(empty($extra)){
            return "";
        }
        $fields = '';
        foreach ($extra->children() as $f) {
            if (substr($f->attributes()->tag, 0, 5) == 'LEGAL') {
                $option = explode('_', $f->attributes()->tag)[1];
                $fields .= $option;
                $fields .= ': ';
                $fields .= $f;
                $fields .= ' ';
            }
        }
        $fields .= 'Area(more or less):';

        $area = $this->xml->buildingDetails->area;
        $fields .= $area;
        $fields .= ' ';
        $fields .= $area->attributes()->unit;
        return "<div>
                    <br>
                    <div class='h4'>LEGAL DESCRIPTION</div>
                    <div class='text'>$fields</div>
                </div>";
    }
}

$property = new property();
echo $property->render();
?>
<script>
    var lat = <?php echo $property->getGeoCode()['lat']; ?>;
    var lng = <?php echo $property->getGeoCode()['lng']; ?>;
    var map;
    function initMap() {
        let map = new google.maps.Map(document.getElementById('map'), {
            center: {lat: lat, lng: lng},
            zoom: 14
        });
        let marker = new google.maps.Marker({position: {lat: lat, lng: lng}, map: map});
    }
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCCob1z0kyfJZhfDIcWUyP14bZqiwBoY4I&callback=initMap"
        async defer></script>
</body>
<?php
wp_footer();
?>

