<?php

/**
 * Copyright (c) since 2010 Stripe, Inc. (https://stripe.com)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Stripe <https://support.stripe.com/contact/email>
 * @copyright Since 2010 Stripe, Inc.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class stripe_officialLogJsErrorModuleFrontController extends ModuleFrontController
{
    private const MAX_BODY_BYTES = 8 * 1024; // 8KB
    private const MAX_MSG_CHARS = 1000;

    public function __construct()
    {
        parent::__construct();
    }

    public function postProcess()
    {
        $raw = file_get_contents('php://input', false, null, 0, self::MAX_BODY_BYTES + 1);
        if ($raw === false || $raw === '') {
            $this->code(400);

            return;
        }
        if (strlen($raw) > self::MAX_BODY_BYTES) {
            $this->code(413);

            return;
        }
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $this->code(400);

            return;
        }

        $msg = isset($payload['msg']) ? trim((string) $payload['msg']) : '';
        if ($msg === '') {
            $this->code(422);

            return;
        }
        if (mb_strlen($msg, 'UTF-8') > self::MAX_MSG_CHARS) {
            $msg = mb_substr($msg, 0, self::MAX_MSG_CHARS, 'UTF-8') . '…';
        }

        $cartId = ($this->context->cart && $this->context->cart->id) ? (int) $this->context->cart->id : 0;
        StripeProcessLogger::logError($msg, 'JavascriptError', $cartId);
        $this->code(204);
    }

    private function code(int $code): void
    {
        http_response_code($code);
        exit;
    }
}
