<?php
/**
 * RSentryClient class file.
 * @author Michael Nel <michael.a.nel1@gmail.com>
 * @copyright Copyright &copy; Michael Nel 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version 0.1
 */

/**
 * Raven-php Sentry Client application component.
 * 
 * A client could be considered a Sentry "Project"
 * @link https://www.getsentry.com/docs/teams-and-projects/
 * 
 * Example configuration:
 * 
 * return array(
 *     'components'=>array(
 *         'sentry'=>array(
 *             'class'=>'ext.yii-sentry.components.RSentryClient',
 *             'dsn'=>'<YOUR_DNS>',
 *             'enabled'=>true,
 *             'options'=>array(
 *                 'tags'=>array(
 *                     'php_version'=>phpversion(),
 *                 )
 *             ),
 *         ),
 *     ),
 * );
 * 
 */
class RSentryClient extends CApplicationComponent {
    
    /**
     * @var string Sentry DSN value
     */
    public $dsn;
    
    /**
     * @var array Raven_Client options
     * @see https://github.com/getsentry/raven-php#configuration
     */
    public $options = array();
    
    /**
     * @var boolean If logging should be performed. This can be useful if 
     * running under development/staging
     */
    public $enabled = true;

    /**
     * @var Raven_Client Stored sentry client connection
     */
    protected $_client = false;
    
	/**
	 * Initializes the RSentryClient component.
	 */
	public function init() {
        // Do not initialise if debugging is active, unless configured otherwise
        if(!$this->enabled) {
            return false;
        }
        
        parent::init();
        
        if(!class_exists('Raven_Autoloader', false)) {
            // Turn off our amazing library autoload
            spl_autoload_unregister(array('YiiBase','autoload'));

            // Include request library
            include(dirname(__FILE__) . '/../lib/Raven/Autoloader.php');

            // Run request autoloader
            Raven_Autoloader::register();

            // Give back the power to Yii
            spl_autoload_register(array('YiiBase','autoload'));
        }

        if($this->_client===false) {
            $this->_client = new Raven_Client($this->dsn, $this->options);
        }
	}
    
    /**
     * Returns true if Yii debug is turned on, false otherwise.
     * @return boolean true if Yii debug is turned on, false otherwise.
     */
    protected function isDebugMode() {
        return defined('YII_DEBUG') && YII_DEBUG === true;
    }
    
    /**
     * Returns the Raven_Client
     * @return Raven_Client The Raven_Client if this component is initialised, 
     * false otherwise.
     */
    public function getRavenClient() {
        return $this->_client;
    }
    
    
    /**************************************************************************
     * Exposed Raven_Client methods
     **************************************************************************/

    /**
     * Log a message to sentry
     */
    public function captureMessage($message, $params=array(), 
                        $level_or_options=array(), $stack=false, $vars = null) {
        // Pass along to the client
        return $this->_client->captureMessage($message, $params, 
                                            $level_or_options, $stack, $vars);
    }

    /**
     * Given an identifier, returns a Sentry searchable string.
     */
    public function getIdent($ident) {
        return $this->_client->getIdent($ident);
    }


    /**
     * Returns the request response from sentry if and only if the last message
     * was not sent successfully.
     * 
     * TODO: Not much documentation around this
     */
    public function getLastError() {
        return $this->_client->getLastError();
    }
    
}