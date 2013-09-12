<?php
/**
 * RSentryLogRoute class file.
 * @author Michael Nel <michael.a.nel1@gmail.com>
 * @copyright Copyright &copy; Michael Nel 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version 0.1
 */

/**
 * Raven-php Sentry LogRoute component.
 * Sends log messages to sentry server.
 * 
 * It is possible to use more than one log route, each sending their log 
 * messages to the same/separate Sentry clients. This is useful when utilising
 * mulitiple Sentry projects.
 * @link https://www.getsentry.com/docs/teams-and-projects/
 */
class RSentryLogRoute extends CLogRoute {
    
    /**
     * @var string Component ID of the sentry client that should be used to 
     * send the logs
     */
    public $sentryComponent = 'sentry';
    
    /**
     * @var string The log category for raven logging related to this extension.
     */
    public $ravenLogCategory = 'raven';
    
    /**
     * @var RSentryClient Sentry client
     */
    protected $_client;
    
    /**
     * @var Raven_ErrorHandler Sentry error handler
     */
    protected $_errorHandler;
    
	/**
	 * Initializes the route.
	 * This method is invoked after the route is created by the route manager.
	 */
    public function init() {
        parent::init();
        
        // Add exception handler if error level required
        if((empty($this->levels) || stristr($this->levels, 'error') !== false)
                && $this->getClient() !== false) {
            Yii::app()->attachEventHandler('onException', array(
                $this,
                'handleException'
            ));
            Yii::app()->attachEventHandler('onError', array(
                $this,
                'handleError'
            ));
            
            // Set up Raven error handler
            $this->_errorHandler = new Raven_ErrorHandler($this->getClient()->getRavenClient());
            $this->_errorHandler->registerShutdownFunction();
        }
    }
    
    /**
     * Send log messages to Sentry.
     * 
     * @param array $logs list of log messages. Each array element represents 
     * one message with the following structure:
	 * array(
	 *   [0] => message (string)
	 *   [1] => level (string)
	 *   [2] => category (string)
	 *   [3] => timestamp (float, obtained by microtime(true)
     * );
     * 
     * @return Sentry event identifier
     */
    protected function processLogs($logs) {
        if(count($logs) == 0) {
            return false;
        }
        
        // Stop processing if RSentryClient not available 
        if(!$sentry = $this->getClient()) {
            return false;
        }
        
        // Process the logs
        foreach($logs as $log) {
            // Don't send exceptions or raven logs
            if(stristr($log[0], 'Stack trace:') !== false
                    || $log[2] == $this->ravenLogCategory){
                continue;
            }
            
            // Get first line of error message
            $format = explode("\n", $log[0]);
            $title = strip_tags($format[0]);
            
            $ident = $sentry->captureMessage($title, array(
                // XX: Not entirely sure how this works, lack of documentation
                'extra'=>array(
                    'category'=>$log[2],
                ),
            ), array(
                'level'=>$log[1],
                'timestamp'=>$log[3],
            ));
            
            // Raven client does not support this, yet
//            $sentry->getIdent($ident);
        }
    }

    /**
     * Send exceptions to sentry server
     * @param CExceptionEvent $event represents the parameter for the 
     * onException event.
     */
    public function handleException($event) {
        // Stop processing if RSentryClient not available 
        if(!$sentry = $this->getClient()) {
            return false;
        }

        $this->_errorHandler->handleException($event->exception);
        
        // If message not sent, log the Sentry server's response
        if ($lastError = $sentry->getLastError()) {
            Yii::log($lastError, CLogger::LEVEL_ERROR, $this->ravenLogCategory);
        }
    }

    /**
     * Send errors to sentry server
     * @param CErrorEvent $event represents the parameter for the onError event.
     */
    public function handleError($event) {
        // Stop processing if RSentryClient not available 
        if(!$sentry = $this->getClient()) {
            return false;
        }

        $this->_errorHandler->handleError(
            $event->code,
            $event->message,
            $event->file,
            $event->line,
            $event->params // slightly different than typical context
        );
        
        // If message not sent, log the Sentry server's response
        if ($lastError = $sentry->getLastError()) {
            Yii::log($lastError, CLogger::LEVEL_ERROR, $this->ravenLogCategory);
        }
    }
    
    /**
     * Returns the RSentryClient which should send the data.
     * It ensure RSentryClient application component exists and is initialised.
     * 
     * @return RSentryClient The configured RSentryClient, or false if the 
     * client is not available
     */
    protected function getClient() {
        if(!isset($this->_client)) {
            if(!Yii::app()->hasComponent($this->sentryComponent)) {
                Yii::log("'$this->sentryComponent' does not exist", 
                        CLogger::LEVEL_TRACE, 'application.RSentryLogRoute');
                $this->_client = false;
            } else {
                $sentry = Yii::app()->{$this->sentryComponent};
                
                if(!$sentry || !$sentry->getIsInitialized()) {
                    Yii::log("'$this->sentryComponent' not initialised", 
                           CLogger::LEVEL_TRACE, 'application.RSentryLogRoute');
                    $this->_client = false;
                } else {
                    $this->_client = $sentry;
                }
            }
        }
        
        return $this->_client;
    }
}
?>
