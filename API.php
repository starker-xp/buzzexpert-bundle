<?php

namespace BuzzExpertBundle\Services\BuzzExpert;

/**
 * API Buzz Expert
 *
 * @version 1.1.4
 */
class API
{

    const WS_HOST = 'web.buzz-expert.fr';
    const WS_IP = '91.209.191.89';

    private $_action = array(
        'push' => '/push',
        'getToken' => '/token',
        'getList' => '/phone-list',
        'getDetailList' => '/phone-list/detail',
        'addPhoneToList' => '/phone-list/add-phone',
        'updatePhoneFromList' => '/phone-list/update-phone',
        'deletePhoneFromList' => '/phone-list/delete-phone',
        'addPhoneToBlackList' => '/phone-list/add-phone-blacklist',
        'deletePhoneFromBlackList' => '/phone-list/delete-phone-blacklist',
        'getPhoneFromCampaign' => '/phone/get',
        'getResponseFromCampaign' => '/response/get',
        'remainCredit' => '/credit/remain',
        'getCampaign' => '/campaign',
        'addOadc' => '/oadc/create',
    );

    private $_lastError;

    private $_token;
    private $_tokenLastUsed;

    private $_curl;

    private $_login;
    private $_password;

    /**
     * Constructeur de l'API
     *
     * @param string $login Nom d'utilisateur
     * @param string $password Mot de passe
     */
    public function __construct($login, $password)
    {
        $this->_login = $login;
        $this->_password = $password;
    }


    private function getToken()
    {
        if (!$this->_token || time() - $this->_tokenLastUsed > 60 * 30) {
            $params['login'] = $this->_login;
            $params['password'] = $this->_password;

            if (!$result = $this->request($params, $this->_action[__FUNCTION__])) {
                return false;
            }

            if (!empty($result['result']['token'])) {
                $this->_token = $result['result']['token'];
                $this->_tokenLastUsed = time();
            }
        }

        return $this->_token;
    }

    /**
     * Récupère l'ensemble des listes de contact
     *
     * @return array
     */
    public function getList()
    {
        $params = array();

        $phoneList = array();
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['phone_list'])) {
                $phoneList = $result['result']['phone_list']['node'];
            }
        }

        return $phoneList;
    }

    /**
     * Récupére les numéros d'une liste
     *
     * @param int $phoneListId Identifiant de la liste
     * @param int $page Numéro de page
     * @param int $limit Nombre de numéro à récupérer
     * @return array
     */
    public function getDetailList($phoneListId, $page = 1, $limit = 200)
    {
        $params['phone_list_id'] = (int) $phoneListId;
        $params['page'] = (int) $page;
        $params['limit'] = (int) $limit;

        $phoneList = array();
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['phone_list'])) {
                $phoneList['id'] = $result['result']['phone_list']['id'];
                $phoneList['phones'] = $result['result']['phone_list']['phones']['node'];
            }
        }

        return $phoneList;
    }


    /**
     * Récupère l'ensemble des campagnes de l'utilisateur
     *
     * @param  string $realType Type de campagne à filtrer
     * @param int $page Numéro de page
     * @param int $limit Nombre de numéro à récupérer
     * @return array
     */
    public function getCampaign($realType = null, $page = 1, $limit = 200)
    {
        $params = array();

        $params['real_type'] = $realType;
        $params['page'] = (int) $page;
        $params['limit'] = (int) $limit;

        $phoneList = array();
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['campaign'])) {
                $phoneList = $result['result']['campaign']['node'];
            }
        }

        return $phoneList;
    }


    /**
     * Récupére les numéros
     *
     * @param int $campaignId Identifiant de la campagne
     * @param int $page Numéro de page
     * @param int $limit Nombre de numéro à récupérer
     * @return array
     */
    public function getPhoneFromCampaign($campaignId, $page = 1, $limit = 200)
    {
        $params['campaign_id'] = (int) $campaignId;
        $params['page'] = (int) $page;
        $params['limit'] = (int) $limit;

        $phoneList = array();
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['phones'])) {
                $phoneList['phones'] = $result['result']['phones']['node'];
            }
        }

        return $phoneList;
    }


    /**
     * Récupère les (SMS) réponses
     *
     * @param int $campaignId Identifiant de la campagne
     * @param int $page Numéro de page
     * @param int $limit Nombre de numéro à récupérer
     * @return array
     */
    public function getResponseFromCampaign($campaignId, $page = 1, $limit = 200)
    {
        $params['campaign_id'] = (int) $campaignId;
        $params['page'] = (int) $page;
        $params['limit'] = (int) $limit;

        $responseList = array();
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['responses'])) {
                $responseList['responses'] = $result['result']['responses']['node'];
            }
        }

        return $responseList;
    }


    /**
     * Ajout/mise à jour d'un nouveau numéro dans une liste
     *
     * @param int $phoneListId Identifiant de la liste
     * @param int $number Numéro à ajouter
     * @param array $variables Variables appartenant au numéro
     * @param int $replace 1 ou 0, 1 => Si le contact existe il sera mise à jour
     * @return int Id du numéro inséré
     */
    public function addPhoneToList($phoneListId, $number, $variables = array(), $replace = 1)
    {
        $params['phone_list_id'] = (int) $phoneListId;
        $params['number'] = $number;
        $params['variables'] = $variables;
        $params['replace'] = (int) $replace;

        $phoneId = false;
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['phone_id'])) {
                $phoneId = (int) $result['result']['phone_id'];
            }
        }

        return $phoneId;
    }

    /**
     * Mise à jour d'un contact uniquement qui existe dans la liste
     *
     * @param int $phoneListId
     * @param int $number
     * @param array $variables
     * @return boolean
     */
    public function updatePhoneFromList($phoneListId, $number, $variables = array())
    {
        $params['phone_list_id'] = (int) $phoneListId;
        $params['number'] = $number;
        $params['variables'] = $variables;

        $response = false;
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['status']) && $result['result']['status'] === 'ok') {
                $response = true;
            }
        }
        return $response;
    }

    /**
     * Suppression d'un numéro d'une liste
     *
     * @param int $phoneListId
     * @param int $number
     * @return boolean
     */
    public function deletePhoneFromList($phoneListId, $number)
    {
        $params['phone_list_id'] = (int) $phoneListId;
        $params['number'] = $number;

        $response = false;
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['status']) && $result['result']['status'] === 'ok') {
                $response = true;
            }
        }

        return $response;
    }

    /**
     * Blacklist un numéro
     *
     * @param int $number
     * @return boolean
     */
    public function addPhoneToBlackList($number)
    {
        $params['number'] = $number;

        $response = false;
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['status']) && $result['result']['status'] === 'ok') {
                $response = true;
            }
        }

        return $response;
    }

    /**
     * Supprime un numéro de la blacklist
     *
     * @param int $number
     * @return boolean
     */
    public function deletePhoneFromBlackList($number)
    {
        $params['number'] = $number;

        $response = false;
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['status']) && $result['result']['status'] === 'ok') {
                $response = true;
            }
        }

        return $response;
    }

    /**
     * Effectue un push (SMS/MMS)
     *
     * @param int $number Destinataire
     * @param string $type Type de push (SMS/MMS)
     * @param array $medias Liste des médias à envoyer
     * @param array $options Options du push
     * @return array
     */
    public function push($number, $type, $medias, $options)
    {
        $params['number'] = $number;
        $params['type'] = $type;
        $params['medias'] = $medias;
        $params['options'] = $options;

        if ($type === 'MMS' || $type === 'VOICE') {
            foreach ($params['medias'] as &$media) {
                if (is_file($media) && is_readable($media)) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $typeMime = finfo_file($finfo, $media);

                    $parameters = '';
                    if ($type === 'MMS') {
                        $filePath = explode('/', $media);
                        $fileName = end($filePath);
                        $parameters = 'filename=' . $fileName . ';';
                    }

                    $media = 'data:' . $typeMime . ';' . $parameters . 'base64,' . base64_encode(file_get_contents($media));
                } else {
                    $media = 'data:text/plain;base64,' . base64_encode($media);
                }
            }
        }

        $response = false;
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['status']) && $result['result']['status'] === 'ok') {
                $response = $result['result'];
            }
        }

        return $response;
    }

    /**
     * Retourne le crédit restant
     *
     * @return boolean
     */
    public function remainCredit()
    {
        $params = array();

        $credit = false;
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['credit'])) {
                $credit = $result['result']['credit'];
            }
        }

        return $credit;
    }

    /**
     * Permet d'ajouter un OADC
     *
     * @param string $oadc valeur de l'OADC
     * @param boolean $favorite permet de définir que cet OADC est favori
     * @return boolean
     */
    public function addOadc($oadc, $favorite)
    {
        $params = array(
            'value' => $oadc,
            'favorite' => $favorite ? '1' : '0'
        );

        $response = false;
        if ($result = $this->request($params, $this->_action[__FUNCTION__])) {
            if (!empty($result['result']['status']) && $result['result']['status'] === 'ok') {
                $response = true;
            }
        }

        return $response;
    }

    private function request($params, $action)
    {
        if ($action !== '/token') {
            if (!$params['token'] = $this->getToken()) {
                return false;
            }
        }
        $request = $this->arrayToXML($params, null, 'request');

        $this->initCurl($request, $action);

        if (!$response = curl_exec($this->_curl)) {
            $this->_lastError['error'] = curl_error($this->_curl);
            $this->_lastError['errno'] = curl_errno($this->_curl);

            $response = false;
        }

        if (!$xmlResponse = simplexml_load_string($response)) {
            $this->_lastError['error'] = 'XML de réponse mal formatté';
            $this->_lastError['errno'] = '';
        }

        $response = $this->xmlToArray($xmlResponse);

        if (!empty($response['result']['status']) && $response['result']['status'] === 'ko') {
            $this->_lastError['error'] = $response['result']['error'];
            $this->_lastError['errno'] = $response['result']['errno'];

            $response = false;
        }

        return $response;
    }

    private function initCurl($request, $action)
    {
        $headers = array(
            'Content-type: application/x-www-form-urlencoded; charset=UTF-8',
            'Connection: Keep-Alive'
        );

        $this->_curl = curl_init('https://' . self::WS_HOST . '/api' . $action);

        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->_curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->_curl, CURLOPT_POST, 1);
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, 'request=' . $request);
    }

    /**
     * Renvoi la dernière erreur générée par l'API
     *
     * @return array
     */
    public function getLastError()
    {
        return $this->_lastError;
    }

    public function setLastError($error)
    {
        $this->_lastError = $error;
    }

    private function arrayToXML($data, $sxe = null, $rootNode = 'xml')
    {
        if ($sxe === null) {
            $sxe = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><' . $rootNode . ' />');
        }

        foreach ($data as $key => $value) {
            $key = is_numeric($key) ? 'node' : preg_replace('/[^a-z:_-]/i', '', $key);
            if (is_array($value)) {
                $this->arrayToXML($value, $sxe->addChild($key));
            } else {
                $sxe->addChild($key, $value);
            }
        }

        return $sxe->asXML();
    }

    private function xmlToArray($sxe)
    {
        return json_decode(json_encode((array) $sxe), true);
    }

}
