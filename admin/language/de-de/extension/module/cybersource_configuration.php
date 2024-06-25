<?php

// Heading Message
$_['heading_title']                                     = 'Cybersource Konfiguration';

// Text Messages
$_['text_download_path']                                = 'Downloadpfad';
$_['text_ex_foldername']                                = 'Bsp.: Ordnername';
$_['text_voided_status']                                = 'Entwertet';
$_['text_partial_voided_status']                        = 'Teilweise ungültig';
$_['text_canceled_status']                              = 'Abgesagt';
$_['text_fraud_review_status']                          = 'Zahlung steht zur Überprüfung aus';
$_['text_fraud_reject_status']                          = 'Bestellung vom Händler storniert';
$_['text_refunded_status']                              = 'Zurückerstattet';
$_['text_partial_refunded_status']                      = 'Teilweise erstattet';
$_['text_processed_status']                             = 'Verarbeitet';
$_['text_processing_status']                            = 'wird bearbeitet';
$_['text_pending_status']                               = 'Ausstehend';
$_['text_authorization']                                = 'Autorisieren';
$_['text_sale']                                         = 'Verkauf';

$_['text_extension']                                    = 'Erweiterungen';
$_['text_success']                                      = 'Erfolg: Sie haben das Cybersource-Konfigurationsmodul modifiziert.';
$_['text_edit']                                         = 'Cybersource-Konfigurationsmodul bearbeiten';

// Tab Messages
$_['tab_general']                                       = 'Allgemeine Konfiguration';
$_['tab_report']                                        = 'Berichtskonfiguration';
$_['tab_status']                                        = 'Konfiguration des Auftragsstatus';

// Entry Messages
$_['entry_sandbox']                                     = 'Sandkasten';
$_['entry_merchant_id']                                 = 'Händler-ID';
$_['entry_merchant_key_id']                             = 'Händlerschlüssel-ID';
$_['entry_merchant_secret_key']                         = 'Geheimer Händlerschlüssel';
$_['entry_fraud_status']                                = 'Betrugsmanagement';
$_['entry_developer_id']                                = 'Entwickler-ID';
$_['entry_dfp']                                         = 'Gerätefingerabdruck';
$_['entry_enhanced_logs']                               = 'Erweiterte Protokolle';
$_['entry_recaptcha_status']                            = 'Google reCAPTCHA';
$_['entry_secret_key']                                  = 'reCAPTCHA Geheimer Schlüssel';
$_['entry_site_key']                                    = 'reCAPTCHA Site-Schlüssel';
$_['entry_status']                                      = 'Status';
$_['entry_auth_status']                                 = 'Autorisierungs Status';
$_['entry_auth_reversal_status']                        = 'Auth-Umkehrung Status';
$_['entry_partial_capture_status']                      = 'Teilerfassung Status';
$_['entry_capture_status']                              = 'Ergreifen Status';
$_['entry_refund_status']                               = 'Erstattung Status';
$_['entry_void_status']                                 = 'Leere Status';
$_['entry_partial_refund_status']                       = 'Teilerstattung Status';
$_['entry_partial_void_status']	                        = 'Teilleere Status';
$_['entry_fraud_management_status']                     = 'Betrugsmanagement Status';
$_['entry_fraud_reject_status']                         = 'Betrugsablehnungsstatus';
$_['entry_payment_error_status']                        = 'Zahlungsfehler Status';
$_['entry_void_error_status']                           = 'Ungültiger Fehler Status';
$_['entry_refund_error_status']                         = 'Rückerstattungsfehler Status';
$_['entry_auth_reversal_error_status']                  = 'Fehler bei der Auth-Umkehrung Status';
$_['entry_payment_batch_detail_report']                 = 'Payment Batch Detail Report';
$_['entry_transaction_request_report']                  = 'Transaction Request Report';
$_['entry_conversion_detail_report']                    = 'Conversion Detail Report';
$_['entry_payment_batch_detail_report_path_test']       = 'Payment Batch Detail Report';
$_['entry_transaction_request_report_path_test']        = 'Transaction Request Report';
$_['entry_payment_batch_detail_report_path_live']       = 'Payment Batch Detail Report';
$_['entry_transaction_request_report_path_live']        = 'Transaction Request Report';
$_['entry_relative_path_test']                          = 'Standardmäßig wird der Bericht gespeichert in {{OpenCartErweiterung
    Installationsverzeichnis}/system/storage
    /Downloads/Reports/Sandbox}. Geben Sie {FolderName} für den benutzerdefinierten Ordner an und er wird im Berichtsverzeichnis erstellt';
$_['entry_relative_path_live']                          = 'Standardmäßig wird der Bericht gespeichert in {{OpenCartErweiterung
    Installationsverzeichnis}/system/storage
    /Downloads/Reports/Production}. Geben Sie {FolderName} für den benutzerdefinierten Ordner an und er wird im Berichtsverzeichnis erstellt';
$_['entry_transaction_method']	 	                    = 'Zahlungsaktion';
$_['entry_dav_status']                                  = 'Überprüfung der Lieferadresse';

// Help Messages
$_['help_developer_id'] 			                    = 'Kennung des Entwicklers, der bei der Integration einer Partnerlösung in Cybersource geholfen hat.';
$_['help_merchant_id']                                  = 'Erstellen Sie ein Cybersource-Konto mit der folgenden URL https://ebc2.cybersource.com/ebc2/
                                                           registration/external';
$_['help_merchant_key_id']                              = 'Erstellen Sie die Händlerschlüssel-ID mit der folgenden URL https://docs.cybersource.com/
                                                          content/dam/documentation/en/
                                                          admin/security-keys/
                                                          creating_and_using_
                                                          security_keys.pdf';
$_['help_merchant_secret_key']                          = 'Erstellen Sie den geheimen Händlerschlüssel mit der folgenden URL https://docs.cybersource.com/
                                                           content/dam/documentation/en/
                                                           admin/security-keys/
                                                           creating_and_using_
                                                           security_keys.pdf';
$_['help_report_guide']                                 = 'https://developer.cybersource.com/
                                                           api/developer-guides/
                                                           dita-reporting-rest-api-dev-guide-102718/
                                                           reporting_api.html';
$_['help_pbd_guide']                                    = 'Der Bericht „Zahlungsstapelzusammenfassung“ zeigt den Gesamtumsatz und die Rückerstattungen nach Währung und Zahlungsmethode an. 
                                        https://developer.cybersource.com/
                                        api/developer-guides/
                                        dita-reporting-rest-api-dev-guide-102718/
                                        reporting_api/reporting-payment-batch-summary-download.html';
$_['help_cdr_guide']                                    = 'Der Conversion-Detailbericht enthält Details zu Transaktionen für einen Händler. 
                                        https://developer.cybersource.com/
                                        api/developer-guides/
                                        dita-reporting-rest-api-dev-guide-102718/reporting_api/
                                        reporting-ondemand-detail-download.html';
$_['help_site_key']                                     = 'Erstellen Sie den Google reCAPTCHA v3 Site Key mit der folgenden URL https://www.google.com/recaptcha/
                                                           admin/create';
$_['help_secret_key']                                   = 'Erstellen Sie den geheimen Google reCAPTCHA v3-Schlüssel mit der folgenden URL https://www.google.com/recaptcha/
                                                           admin/create';

// Error Messages
$_['error_config_form']                                 = 'Warnung:Bitte überprüfen Sie das Formular sorgfältig auf Fehler!';
$_['error_permission']                                  = 'Warnung:Sie haben keine Berechtigung zum Ändern des Cybersource-Konfigurationsmoduls!';
$_['error_secret_key']                                  = 'Bitte geben Sie den geheimen reCAPTCHA-Schlüssel ein';
$_['error_site_key']                                    = 'Bitte reCAPTCHA Site Key eingeben';
$_['error_void']                                        = 'Ungültiger Fehler';
$_['error_cancel']                                      = 'Fehler abbrechen';
$_['error_refund']                                      = 'Rückerstattungsfehler';
$_['error_payment']                                     = 'Zahlungsfehler';
$_['error_invalid_folder_path']                         = 'Der Downloadpfad ist ungültig. Geben Sie einen gültigen Wert ein.';
$_['error_merchant_id']                                 = 'Händlerausweis erforderlich!';
$_['error_invalid_merchant_id']                         = 'Ungültige Händler-ID';
$_['error_merchant_key_id']                             = 'Händlerschlüssel-ID erforderlich!';
$_['error_invalid_merchant_key_id']                     = 'Ungültige Händlerschlüssel-ID';
$_['error_merchant_secret_key']                         = 'Geheimer Schlüssel des Händlers erforderlich!';
$_['error_invalid_merchant_secret_key']                 = 'Ungültiger geheimer Händlerschlüssel';
$_['error_invalid_developer_id']                        = 'Ungültige Entwickler-ID';
