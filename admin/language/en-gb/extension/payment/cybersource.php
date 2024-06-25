<?php

/*
 * English
 *
 */

// Heading
$_['heading_title']                  = 'Cybersource Unified Checkout';

// Text Messages
$_['text_cybersource']               = '<a target="_BLANK" href="https://www.cybersource.com/"><img src="view/image/payment/cybersource.png" alt="Cybersource" title="Cybersource" style="border: 1px solid #EEEEEE;" /></a>';
$_['text_success']                   = 'Success: You have modified Cybersource Unified Checkout payment module!';
$_['text_edit']                      = 'Edit Cybersource Unified Checkout';

// Entry
$_['entry_test']                                  = 'Test mode';
$_['entry_tokenization_status']                   = 'Tokenization';
$_['entry_saved_card_limit_frame']                = 'Saved Card Limit Count';
$_['entry_limit_saved_card_rate']                 = 'Limit Saved Card Rate';
$_['entry_payer_auth_challenge']                  = 'Enforce SCA for Saving Card';
$_['entry_saved_card_limit_time_frame']           = 'Saved Card Limit Time Frame';
$_['entry_attempts_limit_count']                  = 'Attempts Limit Count';
$_['entry_payer_auth_status']                     = 'Payer Authentication';
$_['entry_google_pay']                            = 'Google Pay';
$_['entry_click_to_pay']                          = 'Click to Pay';
$_['entry_payment_option_label']                  = 'Payment Option Label';
$_['entry_allowed_card_types']                    = 'Allowed Card Types';
$_['entry_network_token_updates_status']           = 'Network Token Updates';

// Error
$_['error_saved_card_limit_frame']       = 'Enter valid saved card limit frame';
$_['error_saved_card_limit_time_frame']  = 'Enter valid saved card limit time frame( Min 1 hour and Max 24 hour)';
$_['error_payment_option_label']         = 'Payment option label required!';
$_['error_allowed_card_types']           = 'Please select atleast one card';

// Help
$_['help_enable_save_card']          = 'Enable save card limit feature';
$_['help_number_of_cards']           = 'Provide the number of cards that can be saved in certain time period';
$_['help_number_of_hours']           = 'Provide the number of hours that saved card attempts are counted';
$_['help_attempt_limit']             = 'Provide attempt limit';
$_['help_payer_auth_challenge']      = 'If enabled, card holder will be 3DS challenged when saving a card (Enforcing Strong Customer Authentication)';
$_['help_vsrc']                      = 'Enables Click to Pay in Unified Checkout';
$_['help_gpay']                      = 'Enables Google Pay in Unified Checkout';
$_['help_payer_auth']                = 'Only applicable for Credit Card';
$_['help_payment_option_label']      = 'Payment option label which will be displayed to customer';
$_['help_entry_status']              = 'By enabling status credit card will be enabled by default';
$_['help_network_token_updates']      = 'Subscribe to Network Token life cycle updates';
