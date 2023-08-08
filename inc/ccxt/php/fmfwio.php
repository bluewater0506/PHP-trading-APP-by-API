<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import

class fmfwio extends hitbtc {

    public function describe() {
        return $this->deep_extend(parent::describe (), array(
            'id' => 'fmfwio',
            'name' => 'FMFW.io',
            'countries' => array( 'KN' ),
            'urls' => array(
                'logo' => 'https://user-images.githubusercontent.com/1294454/159177712-b685b40c-5269-4cea-ac83-f7894c49525d.jpg',
                'api' => array(
                    'public' => 'https://api.fmfw.io',
                    'private' => 'https://api.fmfw.io',
                ),
                'www' => 'https://fmfw.io',
                'doc' => 'https://api.fmfw.io/api/2/explore/',
                'fees' => 'https://fmfw.io/fees-and-limits',
                'referral' => 'https://fmfw.io/referral/da948b21d6c92d69',
            ),
            'fees' => array(
                'trading' => array(
                    'maker' => $this->parse_number('0.005'),
                    'taker' => $this->parse_number('0.005'),
                ),
            ),
        ));
    }
}
