<?php

// Heading Message
$_['heading_title']                                     = 'Cybersource Configuration';

// Text Messages
$_['text_download_path']                                = 'Download path';
$_['text_ex_foldername']                                = 'EX: FolderName';
$_['text_voided_status']                                = 'Voided';
$_['text_partial_voided_status']                        = 'Partial Voided';
$_['text_canceled_status']                              = 'Canceled';
$_['text_fraud_review_status']                          = 'Payment pending for review';
$_['text_fraud_reject_status']                          = 'Order cancelled by merchant';
$_['text_refunded_status']                              = 'Refunded';
$_['text_partial_refunded_status']                      = 'Partial Refunded';
$_['text_processed_status']                             = 'Processed';
$_['text_processing_status']                            = 'Processing';
$_['text_pending_status']                               = 'Pending';
$_['text_extension']                                    = 'Extensions';
$_['text_success']                                      = 'Success: You have modified Cybersource Configuration module.';
$_['text_edit']                                         = 'Edit Cybersource Configuration Module';
$_['text_authorization']                                = 'Authorize';
$_['text_sale']                                         = 'Sale';

// Tab Messages
$_['tab_general']                                       = 'General Configuration';
$_['tab_report']                                        = 'Report Configuration';
$_['tab_status']                                        = 'Order Status Configuration';

// Entry Messages
$_['entry_sandbox']                                     = 'Sandbox';
$_['entry_merchant_id']                                 = 'Merchant ID';
$_['entry_merchant_key_id']                             = 'Merchant Key ID';
$_['entry_merchant_secret_key']                         = 'Merchant Secret Key';
$_['entry_fraud_status']                                = 'Fraud Management';
$_['entry_developer_id']                                = 'Developer ID';
$_['entry_dfp']                                         = 'Device Fingerprint';
$_['entry_enhanced_logs']                               = 'Enhanced Logs';
$_['entry_recaptcha_status']                            = 'Google reCAPTCHA ';
$_['entry_secret_key']                                  = 'reCAPTCHA Secret key';
$_['entry_site_key']                                    = 'reCAPTCHA Site key';
$_['entry_status']                                      = 'Status';
$_['entry_auth_status']                                 = 'Authorization Status';
$_['entry_auth_reversal_status']                        = 'Auth Reversal Status';
$_['entry_partial_capture_status']                      = 'Partial Capture Status';
$_['entry_capture_status']                              = 'Capture Status';
$_['entry_refund_status']                               = 'Refund Status';
$_['entry_void_status']	                                = 'Void Status';
$_['entry_partial_refund_status']                       = 'Partial Refund Status';
$_['entry_partial_void_status']	                        = 'Partial Void Status';
$_['entry_fraud_management_status']                     = 'Fraud Management Status';
$_['entry_fraud_reject_status']                         = 'Fraud Reject Status';
$_['entry_payment_error_status']                        = 'Payment Error Status';
$_['entry_void_error_status']                           = 'Void Error Status';
$_['entry_refund_error_status']                         = 'Refund Error Status';
$_['entry_auth_reversal_error_status']                  = 'Auth Reversal Error Status';
$_['entry_payment_batch_detail_report']                 = 'Payment Batch Detail Report';
$_['entry_transaction_request_report']                  = 'Transaction Request Report';
$_['entry_conversion_detail_report']                    = 'Conversion Detail Report';
$_['entry_payment_batch_detail_report_path_test']       = 'Payment Batch Detail Report';
$_['entry_transaction_request_report_path_test']        = 'Transaction Request Report';
$_['entry_payment_batch_detail_report_path_live']       = 'Payment Batch Detail Report';
$_['entry_transaction_request_report_path_live']        = 'Transaction Request Report';
$_['entry_relative_path_test']                          = 'By default report will be saved in {{OpenCartExtension
    InstallationDirectory}/system/storage
    /Downloads/Reports/Sandbox}. Provide {FolderName} for custom folder and it will be created inside Reports directory.';
$_['entry_relative_path_live']                          = 'By default report will be saved in {{OpenCartExtension
    InstallationDirectory}/system/storage
    /Downloads/Reports/Production}. Provide {FolderName} for custom folder and it will be created inside Reports directory.';
$_['entry_transaction_method']                          = 'Payment Action';
$_['entry_dav_status']                                  = 'Delivery Address Verification';

// Help Messages
$_['help_developer_id']                                 = 'Identifier for the developer that helped integrate a partner solution to Cybersource.';
$_['help_merchant_id']                                  = 'Create Cybersource account using the below url https://ebc2.cybersource.com/ebc2/
                                        registration/external';
$_['help_merchant_key_id']                              = 'Create Merchant Key ID using the below url https://docs.cybersource.com/
                                        content/dam/documentation/en/
                                        admin/security-keys/
                                        creating_and_using_
                                        security_keys.pdf';
$_['help_merchant_secret_key']                          = 'Create Merchant Secret Key using the below url https://docs.cybersource.com/
                                        content/dam/documentation/en/
                                        admin/security-keys/
                                        creating_and_using_
                                        security_keys.pdf';
$_['help_report_guide']                                 = 'https://developer.cybersource.com/
                                        api/developer-guides/
                                        dita-reporting-rest-api-dev-guide-102718/
                                        reporting_api.html';
$_['help_pbd_guide']                                    = 'The Payment Batch Summary report shows total sales and refunds by currency and payment method. 
                                        https://developer.cybersource.com/
                                        api/developer-guides/
                                        dita-reporting-rest-api-dev-guide-102718/
                                        reporting_api/reporting-payment-batch-summary-download.html';
$_['help_cdr_guide']                                    = 'The Conversion Detail Report contains details of transactions for a merchant. 
                                        https://developer.cybersource.com/
                                        api/developer-guides/
                                        dita-reporting-rest-api-dev-guide-102718/reporting_api/
                                        reporting-ondemand-detail-download.html';
$_['help_site_key']                                     = 'Create Google reCAPTCHA v3 Site Key using the below url https://www.google.com/recaptcha/
                                                           admin/create';
$_['help_secret_key']                                   = 'Create Google reCAPTCHA v3 Secret Key using the below url https://www.google.com/recaptcha/
                                                           admin/create';

// Error Messages
$_['error_config_form']                                 = 'Warning: Please check the form carefully for errors!';
$_['error_permission']                                  = 'Warning: You do not have permission to modify Cybersource Configuration module!';
$_['error_secret_key']                                  = 'Please enter reCAPTCHA Secret Key';
$_['error_site_key']                                    = 'Please enter reCAPTCHA Site Key';
$_['error_void']                                        = 'Void Error';
$_['error_cancel']                                      = 'Cancel Error';
$_['error_refund']                                      = 'Refund Error';
$_['error_payment']                                     = 'Payment Error';
$_['error_invalid_folder_path']                         = 'Download path is invalid. Please enter valid value.';
$_['error_merchant_id']                                 = 'Merchant ID required!';
$_['error_invalid_merchant_id']                         = 'Invalid Merchant ID';
$_['error_merchant_key_id']                             = 'Merchant Key ID required!';
$_['error_invalid_merchant_key_id']                     = 'Invalid Merchant Key ID';
$_['error_merchant_secret_key']                         = 'Merchant Secret Key required!';
$_['error_invalid_merchant_secret_key']                 = 'Invalid Merchant Secret Key';
$_['error_invalid_developer_id']                        = 'Invalid Developer ID';
