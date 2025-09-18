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

/*
 * Smarty modifier to replace HTML tags in translations.
 * @usage {{l='test'}|totlreplace}
 * @param.value string
 * @param.name string
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!function_exists('smarty_modifier_stripelreplace')) {
    function smarty_modifier_stripelreplace($string, $replaces = [])
    {
        $search = [
            '[b]',
            '[/b]',
            '[br]',
            '[em]',
            '[/em]',
            '[a @href1@]',
            '[a @href2@]',
            '[/a]',
            '[small]',
            '[/small]',
            '[strong]',
            '[/strong]',
            '[i]',
            '[/i]',
        ];
        $replace = [
            '<b>',
            '</b>',
            '<br>',
            '<em>',
            '</em>',
            '<a href="@href1@" @target@>',
            '<a href="@href2@" @target@>',
            '</a>',
            '<small>',
            '</small>',
            '<strong>',
            '</strong>',
            '<i>',
            '</i>',
        ];
        $string = str_replace($search, $replace, $string);
        foreach ($replaces as $k => $v) {
            $string = str_replace($k, $v, $string);
        }
        $string = str_replace(' @target@', '', $string);

        return $string;
    }
}
