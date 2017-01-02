<?php

/**
 * Class BoschIndego
 * Führt den Login durch, gibt Informationen zum aktuellen Zustand aus und kann Aktionen am Indego ausführen.
 */
class BoschIndego {

    protected $contextId;
    protected $userId;
    protected $alm_sn;
    protected $base_url;

    /**
     * BoschIndego constructor.
     *
     * @param $username
     * @param $password
     */
    public function __construct($username, $password) {
        $this->setBaseUrl("https://api.indego.iot.bosch-si.com/api/v1/");
        $response = $this->doAuthentication($username, $password);
        $this->setContectId($response->contextId);
        $this->setUserId($response->userId);
        $this->setAlmSn($response->alm_sn);
    }

    /**
     * @param $base_url
     */
    protected function setBaseUrl($base_url) {
        $this->base_url = $base_url;
    }

    /**
     * @return String
     */
    protected function getBaseUrl() {
        return $this->base_url;
    }

    /**
     * @param $contextId
     */
    protected function setContectId($contextId) {
        $this->contextId = $contextId;
    }

    /**
     * @return mixed
     */
    protected function getContextId() {
        return $this->contextId;
    }

    /**
     * @param $userId
     */
    protected function setUserId($userId) {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    protected function getUserId() {
        return $this->userId;
    }

    /**
     * @param $almSn
     */
    protected function setAlmSn($almSn) {
        $this->alm_sn = $almSn;
    }

    /**
     * @return mixed
     */
    protected function getAlmSn() {
        return $this->alm_sn;
    }

    /**
     * Führt eine Aktion aus
     * Mögliche Aktionen:
     *  - mähen (mow)
     *  - pausieren (pause)
     *  - zurück zur Ladestation (returnToDock)
     *
     * @param $action
     */
    public function doAction($action) {
        //whitelisting der Aktionen - security
        $available_actions = array("mow", "pause", "returnToDock");
        if(!in_array($action, $available_actions)) {
            echo "failed";
            exit;
        }
        $data       = array("state" => $action);
        $url        = $this->getBaseUrl() . "alms/" . $this->getAlmSn() . "/state";
        $ch         = curl_init($url);
        $headers    = array();
        $headers[]  = 'x-im-context-id: ' . $this->getContextId();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));

        curl_exec($ch);
        $info = curl_getinfo($ch);
        echo "<br /><b>" . (($info["http_code"] == 200) ? "Action $action successfully sent" : "ERROR while sending data") . "</b>";
    }

    /**
     * Firmware Stand - Service benötigt?
     */
    public function getFirmware() {
        $url        = $this->getBaseUrl() . "alms/" . $this->getAlmSn() . "";
        $curl       = curl_init();
        $headers    = array();
        $headers[]  = 'x-im-context-id: ' . $this->getContextId();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = json_decode(curl_exec($curl));
        echo "<pre>Service needed: " . (($result->needs_service == false) ? "no" : "yes") . "<br /></pre>";
        curl_close($curl);
    }

    /**
     * Gibt den aktuellen Status aus.
     */
    public function getInformation() {
        $url = $this->getBaseUrl() . "alms/" . $this->getAlmSn() . "/state";

        $curl       = curl_init();
        $headers    = array();
        $headers[]  = 'x-im-context-id: ' . $this->getContextId();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = json_decode(curl_exec($curl));

        $map_update_available   = ($result->map_update_available == false) ? 'no' : 'yes';
        $total_operate_time     = round($result->runtime->total->operate / 60, 2);
        $total_charge_time      = round($result->runtime->total->charge / 60, 2);
        $session_operate_time   = round($result->runtime->session->operate / 60, 2);
        $session_charge_time    = round($result->runtime->session->charge / 60, 2);

        echo "Map Update available: " . $map_update_available . "<br />";
        echo "Mowed: " . $result->mowed . "%<br />";
        echo "Session operate time: " . $session_operate_time . " h<br />";
        echo "Session charge time: " . $session_charge_time . " h<br />";
        echo "Total operate time: " . $total_operate_time . " h<br />";
        echo "Total charge time: " . $total_charge_time . " h<br />";

        curl_close($curl);
    }

    /**
     * Gibt das SVG direkt aus
     */
    public function getMap() {
        $url        = $this->getBaseUrl() . "alms/" . $this->getAlmSn() . "/map";
        $curl       = curl_init();
        $headers    = array();
        $headers[]  = 'x-im-context-id: ' . $this->getContextId();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);

        echo '<span style="width:400px;display:block">' . $result . '</span>';
    }

    /**
     * Gibt die Fehlermeldungen direkt aus (var_dump)
     */
    public function getCalendar() {
        $url        = $this->getBaseUrl() . "alms/" . $this->getAlmSn() . "/calendar";
        $curl       = curl_init();
        $headers    = array();
        $headers[]  = 'x-im-context-id: ' . $this->getContextId();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        echo "Calendar:<br />";
        echo "<pre>";
        var_dump($result);
        echo "</pre>";
    }

    /**
     * Führt den Login durch
     *
     * @param $username
     * @param $password
     * @return String
     */
    private function doAuthentication($username, $password) {
        $url    = $this->getBaseUrl() . 'authenticate';
        $curl   = curl_init();
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);

        return json_decode($result);
    }
}