<?php
if (!class_exists("sp_catVideo")) {

    /**
     * Extends sp_catComponent
     * HTML5 Video category component - used to control the video post component.
     * Adds features to the dashboard to control video post components.
     *
     * @see sp_catComponent
     */
    define( 'DEBUG_SP_VIDEO', false); // Set to true to debug the video component
    define( 'SP_DEFAULT_VIDEO_PATH', '/usr/local/bin/' ); // Default path to begin our search for ffmpeg
    define( 'SP_DEFAULT_VIDEO_ENCODING', true); // Encode by default

    class sp_catVideo extends sp_catComponent{

        public $convertToHTML5 = false;

        function __construct($compID = 0, $catID = 0, $name = '',
                             $description = '', $typeID = 0, $order = 0,
                             $options = null, $default = false, $required = false){

            $compInfo = compact("compID", "catID", "name", "description", "typeID",
                                "options",	"order", "default", "required");


            // Set default video options
            if($compID == 0){
                $sp_ffmpeg_path = get_site_option( 'sp_ffmpeg_path' );

                if( $sp_ffmpeg_path !== false ){
                    $options->convertToHTML5 = true;
                }else{
                    $options->convertToHTML5 = false;
                }

                $this->options = $options;
            }
            $this->initComponent($compInfo);

            // Get updated video options after initializing the component
            $this->convertToHTML5 = $this->options->convertToHTML5;
        }

        /**
         * @see parent::installComponent()
         */
        function install(){

            self::installComponent('Video', 'Basic HTML5 video player', __FILE__);

            // Use /usr/local/bin/ by default
            update_site_option( 'sp_ffmpeg_path', SP_DEFAULT_VIDEO_PATH );
            update_site_option( 'sp_html5_encoding', SP_DEFAULT_VIDEO_ENCODING );

            // See if ffmpeg exists...
            exec( 'command -v ' . $sp_ffmpeg_path . 'ffmpeg', $ffmpeg_output, $ffmpeg_status );

            // If command exited successfully, then update the sp_ffmpeg_path site option with the path, otherwise update it to false.
            if( $ffmpeg_status === '0' ){
                update_site_option( 'sp_ffmpeg_path', $ffmpeg_output );
            }else{
                update_site_option( 'sp_ffmpeg_path', false );
            }

            if( DEBUG_SP_VIDEO ){
                error_log( 'ffmpeg_status: ' . $ffmpeg_status );
                error_log( 'ffmpeg output: ' . print_r($ffmpeg_output, true) );
            }
        }

        /**
         * !TODO: Add as another abstract method to all components and call when plugin is deleted.
         * Removes video component options when plugin is deleted.
         */
        function uninstall(){
            delete_site_option( 'sp_ffmpeg_path' );
        }

        /**
         * Adds CSS / JS to stylize and handle any UI actions
         */
        static function init(){
            require_once( 'ajax/sp_catVideoAJAX.php' );
            sp_catVideoAJAX::init();
            wp_register_script( 'sp_catVideoJS', plugins_url('js/sp_catVideo.js', __FILE__), array('jquery', 'sp_admin_js') );
            wp_enqueue_script( 'sp_catVideoJS' );
        }

        /**
         * @see parent::componentOptions()
         * @return mixed|void
         */
        function componentOptions(){
            echo "No options exist for this component";
        }

        /**
         * @see parent::getOptions()
         */
        function getOptions(){
            return $this->options;
        }

        /**
         * Sets the option of whether to attempt to convert to HTML5 compatible video formats
         * using ffmpeg2theora and HandBrakeCLI command line utilities.
         * @see parent::setOptions()
         * @param $options
         * @return bool|int
         */
        function setOptions($options = null){
            $options = maybe_serialize($options);
            return sp_core::updateVar('sp_catComponents', $this->ID, 'options', $options, '%s');
        }

        /**
         * Renders the global options for this component, otherwise returns false.
         * @return bool|string
         */
        public static function globalOptions(){
            ?>
            <p>
            The Video component uses <a href="http://www.ffmpeg.org/">ffmpeg</a> to format uploaded videos to HTML5 supported *.webm and *.mp4 formats.
            It will try to detect if ffmpeg is available on the server. If it cannot find ffmpeg, it will not attempt at
                formatting uploaded videos via SmartPost.
            </p>
            <?php

            $sp_ffmpeg_path = get_site_option( 'sp_ffmpeg_path' );
            $html5_encoding = get_site_option( 'sp_html5_encoding' ); // whether to enable html5 video encoding

            if(DEBUG_SP_VIDEO){
                exec('command -v ' . $sp_ffmpeg_path . 'ffmpeg', $cmd_output, $cmd_status);
                ?>
                <div class="error">
                    <p>Command: <?php echo 'command -v ' . $sp_ffmpeg_path . 'ffmpeg' ?></p>
                    <p>Status code: <?php echo $cmd_status ?></p>
                    <p>Command ouput: <?php echo print_r( $cmd_output, true) ?></p>
                </div>
            <?php
            }

            if( $sp_ffmpeg_path ){
                $checked = $html5_encoding ? 'checked' : '';
                ?>
                <input type="checkbox" class="enableHTML5Video" id="html5video" <?php echo $checked ?> />
                <label for="html5video">
                    Encode uploaded video to HTML5 compatible formats. It is recommended to check the box
                    to enable this option.
                </label>
            <?php
            }else{
                ?>
                <div id="ffmpeg_not_found" class="error">
                    <p>
                    Doh! ffmpeg was not detected. If you are unsure what to do next, contact your server administrator or post to the WordPress forums for further help.</p>
                </div>
            <?php
            }
            ?>
            <p>
                Full path to the ffmpeg executable:
                <input type="text" id="ffmpeg_path" name="ffmpeg_path" value="<?php echo $sp_ffmpeg_path ?>" />
                <button class="button" type="button" id="check_ffmpeg_path" name="check_ffmpeg_path">Test</button>
            </p>
            <span id="check_ffmpeg_results" style="color: green;"></span>
            <?php
        }
    }
}
?>