<?php
/*****************************************************************/
// function plugin_bluepay_variables($params) - required function
/*****************************************************************/
require_once 'modules/admin/models/GatewayPlugin.php';

/**
* @package Plugins
*/
class PluginBluepay extends GatewayPlugin
{
    function getVariables() {
        /* Specification
              itemkey     - used to identify variable in your other functions
              type        - text,textarea,yesno,password,hidden ( hiddens are not visable to the user )
              description - description of the variable, displayed in ClientExec
              value       - default value
        */
        $variables = array (
                   /*T*/"Plugin Name"/*/T*/ => array (
                                        "type"        =>"hidden",
                                        "description" =>/*T*/"How CE sees this plugin (not to be confused with the Signup Name)"/*/T*/,
                                        "value"       =>/*T*/"BluePay"/*/T*/
                                       ),
                   /*T*/"BluePay Account ID"/*/T*/ => array (
                                        "type"        =>"text",
                                        "description" =>/*T*/"Please enter your BluePay Account ID Here."/*/T*/,
                                        "value"       =>""
                                       ),
                   /*T*/"BluePay Secret Key"/*/T*/ => array (
                                        "type"        =>"password",
                                        "description" =>/*T*/"Please enter your BluePay Secret Key Here."/*/T*/,
                                        "value"       =>""
                                       ),
                   /*T*/"Demo Mode"/*/T*/ => array (
                                        "type"        =>"yesno",
                                        "description" =>/*T*/"Select YES if you want to set this plugin in Demo mode for testing purposes."/*/T*/,
                                        "value"       =>"1"
                                       ),
                   /*T*/"Accept CC Number"/*/T*/ => array (
                                        "type"        =>"hidden",
                                        "description" =>/*T*/"Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"/*/T*/,
                                        "value"       =>"1"
                                       ),
                   /*T*/"Visa"/*/T*/ => array (
                                        "type"        =>"yesno",
                                        "description" =>/*T*/"Select YES to allow Visa card acceptance with this plugin.  No will prevent this card type."/*/T*/,
                                        "value"       =>"1"
                                       ),
                   /*T*/"MasterCard"/*/T*/ => array (
                                        "type"        =>"yesno",
                                        "description" =>/*T*/"Select YES to allow MasterCard acceptance with this plugin. No will prevent this card type."/*/T*/,
                                        "value"       =>"1"
                                       ),
                   /*T*/"AmericanExpress"/*/T*/ => array (
                                        "type"        =>"yesno",
                                        "description" =>/*T*/"Select YES to allow American Express card acceptance with this plugin. No will prevent this card type."/*/T*/,
                                        "value"       =>"1"
                                       ),
                   /*T*/"Discover"/*/T*/ => array (
                                        "type"        =>"yesno",
                                        "description" =>/*T*/"Select YES to allow Discover card acceptance with this plugin. No will prevent this card type."/*/T*/,
                                        "value"       =>"0"
                                       ),
                   /*T*/"Invoice After Signup"/*/T*/ => array (
                                        "type"        =>"yesno",
                                        "description" =>/*T*/"Select YES if you want an invoice sent to the customer after signup is complete."/*/T*/,
                                        "value"       =>"1"
                                       ),
                   /*T*/"Signup Name"/*/T*/ => array (
                                        "type"        =>"text",
                                        "description" =>/*T*/"Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."/*/T*/,
                                        "value"       =>"Credit Card"
                                       ),
                   /*T*/"Dummy Plugin"/*/T*/ => array (
                                        "type"        =>"hidden",
                                        "description" =>/*T*/"1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"/*/T*/,
                                        "value"       =>"0"
                                       ),
                   /*T*/"Auto Payment"/*/T*/ => array (
                                        "type"        =>"hidden",
                                        "description" =>/*T*/"No description"/*/T*/,
                                        "value"       =>"1"
                                       ),
                   /*T*/"30 Day Billing"/*/T*/ => array (
                                        "type"        =>"hidden",
                                        "description" =>/*T*/"Select YES if you want ClientExec to treat monthly billing by 30 day intervals.  If you select NO then the same day will be used to determine intervals."/*/T*/,
                                        "value"       =>"0"
                                       ),
                   /*T*/"Check CVV2"/*/T*/ => array (
                                        "type"          =>"hidden", // not implemented yet
                                        "description"   =>/*T*/"Select YES if you want to accept CVV2 for this plugin."/*/T*/,
                                        "value"         =>"1"
                                       )
        );
        return $variables;
    }

    /*****************************************************************/
    // function plugin_bluepay_singlepayment($params) - required function
    /*****************************************************************/
    function singlepayment($params)
    { // when set to non recurring
        //Function used to provide users with the ability
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_paypal_UserID"])
        return $this->autopayment($params);
    }

    /*****************************************************************/
    // function plugin_bluepay_autopayment($userid) - required function if plugin is autopayment capable
    /*****************************************************************/
    function autopayment($params)
    {
        //used in callback
        $transType = 'charge';

        require_once 'class.BluePay.php';
        $mode = "TEST";
        if (!$params['plugin_bluepay_Demo Mode']) {
            $mode = "LIVE";
        }
        $bluePay = new BluePayment($params['plugin_bluepay_BluePay Account ID'], $params['plugin_bluepay_BluePay Secret Key'], $mode);
        $bluePay->sale($params['invoiceTotal']);
        $bluePay->setCustInfo($params["userCCNumber"],$params["userCCCVV2"],$params["userCCExp"],$params['userFirstName'],$params['userLastName'],
            $params['userAddress'],$params['userCity'],$params['userState'],$params['userZipcode'],$params['userCountry'],
            $params['userPhone'], $params['userEmail'], null, $params['invoiceDescription']);
        $bluePay->process($params["pathCurl"]);

        if ($params['isSignup']==1){
            $bolInSignup = true;
        }else{
            $bolInSignup = false;
        }
        include('plugins/gateways/bluepay/callback.php');
        //Return error code
        $tReturnValue = "";
        if (($bluePay->getStatus()==1)||($bluePay->getStatus()=='*1*')){ $tReturnValue = ""; }
        else { $tReturnValue = $bluePay->getMessage()." Code:".$bluePay->getStatus(); }
        return $tReturnValue;
    }

    function credit($params)
    {
        // used in callback
        $transType = 'refund';

        require_once 'class.BluePay.php';
        $mode = "TEST";
        if (!$params['plugin_bluepay_Demo Mode']) {
            $mode = "LIVE";
        }
        $bluePay = new BluePayment($params['plugin_bluepay_BluePay Account ID'], $params['plugin_bluepay_BluePay Secret Key'], $mode);
        $bluePay->refund($params['invoiceRefundTransactionId']);
        $bluePay->setCustInfo($params["userCCNumber"],"",$params["userCCExp"],$params['userFirstName'],$params['userLastName'],
            $params['userAddress'],$params['userCity'],$params['userState'],$params['userZipcode'],$params['userCountry'],
            $params['userPhone'], $params['userEmail'], null, $params['invoiceDescription']);
        $bluePay->process($params["pathCurl"]);

        if ($params['isSignup']==1){
            $bolInSignup = true;
        }else{
            $bolInSignup = false;
        }
        include('plugins/gateways/bluepay/callback.php');

        //Return error code

        if($bluePay->getStatus() == 1
          || $bluePay->getStatus() == '*1*'){
            return array('AMOUNT' => $params['invoiceTotal']);
        }else{
            return  $bluePay->getMessage()." Code:".$bluePay->getStatus();
        }
    }
}
?>
