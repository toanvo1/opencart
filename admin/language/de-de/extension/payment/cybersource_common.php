<?php

// Help messages
$_['help_configure']                             = 'Geben Sie die Konfigurationsdetails in das Cybersource-Konfigurationsmodul ein';
$_['help_configure_info']                        = 'Sie können Konfigurationsdetails im Cybersource-Konfigurationsmodul aktualisieren';

// Text messages
$_['text_module']                                = 'Module';
$_['text_extension']                             = 'Erweiterungen';
$_['text_button_save']                           = 'Speichern';
$_['text_button_cancel']                         = 'Abbrechen';
$_['text_enabled']                               = 'Aktivieren';
$_['text_disabled']                              = 'Deaktivieren';
$_['text_authorization_details']                 = 'Autorisierungsdetails';
$_['text_transaction_id']                        = 'Transaktions-ID';
$_['text_settlement_details']                    = 'Abrechnungsdetails';
$_['text_transaction_id_s']                      = 'Transaktions-IDs';
$_['text_capture_details']                       = 'Aufnahmedetails';
$_['text_auth_reversal_details']                 = 'Details zur Authentifizierungsaufhebung';
$_['text_refund_details']                        = 'Rückerstattungsdetails';
$_['text_void_capture_details']                  = 'Details zur Leerenerfassung';
$_['text_void_refund_details']                   = 'Details zur Stornierung der Rückerstattung';
$_['text_payment_method']                        = 'Bezahlverfahren';

// Entry messages
$_['entry_sort_order']                           = 'Sortierreihenfolge';
$_['entry_status']                               = 'Status';

// Error messages
$_['error_permission']           	             = 'Warnung: Sie haben keine Berechtigung, die Zahlung Cybersource zu ändern!';
$_['error_null_data']                            = 'Benutzereingaben oder Nulldaten können nicht abgerufen werden.';
$_['error_transaction_not_found']                = 'Transaktionsdetails nicht gefunden!!';

// order_capture content
// Text messages
$_['text_button_partial_capture']                = 'Teilerfassung';
$_['text_button_capture']                        = 'Ergreifen';
$_['text_button_auth_reversal']                  = 'Abbrechen';
$_['text_button_void_capture']                   = 'Leerenerfassung';
$_['text_button_void_refund']                    = 'Rückerstattung stornieren';
$_['text_button_refund']                         = 'Erstattung';
$_['text_button_partial_refund']                 = 'Teilerstattung';
$_['text_partial_capture']                       = 'Fahren Sie mit der teilweisen Aufnahme fort!';
$_['text_capture']                               = 'Möchten Sie wirklich den gesamten Auftrag erfassen?';
$_['text_auth_reversal']                         = 'Möchten Sie wirklich die gesamte Bestellung stornieren?';
$_['text_void_capture']                          = 'Möchten Sie die erfasste Bestellung wirklich stornieren?';
$_['text_refund']                                = 'Möchten Sie wirklich die gesamte Bestellung zurückerstatten?';
$_['text_void_refund']                           = 'Möchten Sie wirklich die gesamte Bestellung stornieren?';
$_['text_order_management']                      = 'Auftragsverwaltung';
$_['text_partial_refund']                        = 'Fahren Sie mit Rückerstattung fort!';
$_['text_include_shipping_cost']                 = 'Inklusive Versandkosten';
$_['text_yes']                                   = 'Jawohl';
$_['text_no']                                    = 'Nein';

// Colunm Data
$_['column_order_id']                            = 'Befehl ID';
$_['column_total']                               = 'Gesamt';
$_['column_product']                             = 'Produkt';
$_['column_model']                               = 'Modell';
$_['column_quantity']                            = 'Menge';
$_['column_capture']                             = 'Ergreifen';
$_['column_price']                               = 'Preis';
$_['column_refund']                              = 'Erstattung';
$_['column_basePrice']                           = 'Grundpreis';
$_['column_capture_quantity']                    = 'Zu erfassende Menge';
$_['column_refund_quantity']                     = 'Zu erstattende MengeQuantity to be refunded';

// Error messages
$_['error_capture_quantity']                     = 'Bitte gültige Menge eingeben';

// Messages
// success messages
$_['success_msg_capture']                        = 'Erfolg: Eine Standarderfassung wurde erfolgreich erstellt.';
$_['success_msg_partial_capture']                = 'Erfolg: Eine teilweise Erfassung wurde erfolgreich erstellt.';
$_['success_msg_void_capture']                   = 'Erfolg: Eine Leererfassung wurde erfolgreich erstellt.';
$_['success_msg_refund']                         = 'Erfolg: Rückerstattung wurde erfolgreich generiert.';
$_['success_msg_void_refund']                    = 'Erfolg: Eine ungültige Rückerstattung wurde erfolgreich erstellt.';
$_['success_msg_partial_void_refund']            = 'Erfolg: Eine teilweise ungültige Rückerstattung wurde erfolgreich erstellt.';
$_['success_msg_auth_reversal']                  = 'Erfolg: Die gesamte Bestellung wurde erfolgreich storniert.';
$_['success_msg_refund_cancelled']               = 'Erfolg: Die gesamte Rückerstattung wurde erfolgreich storniert.';

// error messages
$_['error_msg_capture']                          = 'Fehler: Die Bestellung konnte nicht erfasst werden.';
$_['error_msg_order_details']                    = 'Fehler: Beim Versuch, einige der Bestelldetails abzurufen, ist ein Fehler aufgetreten. Bitte versuche es erneut.';
$_['error_msg_auth_reversal']                    = 'Fehler: Die Bestellung konnte nicht storniert werden.';
$_['error_msg_void_capture']                     = 'Fehler: Die Erfassung konnte nicht storniert werden.';
$_['error_msg_refund']                           = 'Fehler: Die Bestellung konnte nicht zurückerstattet werden.';
$_['error_msg_void_refund']                      = 'Fehler: Die Rückerstattung konnte nicht storniert werden.';
$_['error_msg_data_not_found']                   = 'Fehler: Bestelldetails wurden nicht gefunden!!. Bitte versuche es erneut.';
$_['error_msg_try_again']                        = 'Ihre Anfrage kann nicht bearbeitet werden. Bitte versuchen Sie es nach einiger Zeit erneut.';
$_['error_shipping_cost_select']                 = 'Versandkosten müssen einem Artikel zugeordnet werden.';
$_['error_refund_shipping_select']               = 'Versandkosten einschließen, um mit der Rückerstattung fortzufahren.';

// warning messages
$_['warning_msg_capture_insertion']              = 'Warnung:Die Bestellung wurde erfolgreich erfasst, aber beim Einfügen der Erfassungsdetails in die Erfassungstabelle ist ein Fehler aufgetreten!';
$_['warning_msg_shipping_insertion']             = 'Warnung:Die Bestellung wurde erfolgreich erfasst, aber beim Einfügen von Versanddetails in die Erfassungstabelle ist ein Fehler aufgetreten!';
$_['warning_msg_capture_status_update']          = 'Warnung:Bestellung wurde erfolgreich erfasst, aber beim Aktualisieren des Erfassungsstatus in die Erfassungstabelle ist ein Fehler aufgetreten!';
$_['warning_msg_cancel_completed']               = 'Warnung:Bestellung wurde bereits storniert!';
$_['warning_msg_auth_reversal_insertion']        = 'Warnung:Die Bestellung wurde erfolgreich storniert, aber beim Einfügen von Authentifizierungsstornierungsdetails in die Authentifizierungsstornierungstabelle ist ein Fehler aufgetreten!';
$_['warning_msg_void_capture']                   = 'Warnung:Die Bestellung wurde erfolgreich storniert, aber beim Einfügen von Stornodetails in die Stornoerfassungstabelle ist ein Fehler aufgetreten!';
$_['warning_msg_void_capture_status_update']     = 'Warnung:Bestellung wurde erfolgreich storniert, aber beim Aktualisieren des ungültigen Erfassungsstatus in die Erfassungstabelle ist ein Fehler aufgetreten!';
$_['warning_msg_refund_insertion']               = 'Warnung:Die Bestellung wurde erfolgreich erstattet, aber beim Einfügen der Rückerstattungsdetails in die Rückerstattungstabelle ist ein Fehler aufgetreten!';
$_['warning_msg_refund_capture_status_update']   = 'Warnung:Die Bestellung wurde erfolgreich erstattet, aber beim Aktualisieren des Rückerstattungsstatus in die Erfassungstabelle ist ein Fehler aufgetreten!';
$_['warning_msg_refund_status_update']           = 'Warnung:Die Bestellung wurde erfolgreich erstattet, aber beim Aktualisieren des Rückerstattungsstatus in die Rückerstattungstabelle ist ein Fehler aufgetreten!';
$_['warning_msg_refund_auth_status_update']      = 'Warnung:Die Bestellung wurde erfolgreich erstattet, aber beim Aktualisieren der Rückerstattungsdetails in der Bestelltabelle ist ein Fehler aufgetreten!';
$_['warning_msg_void_refund_insertion']          = 'Warnung:Die Bestellung wurde erfolgreich storniert, aber beim Einfügen von Stornodetails in die Stornorückerstattungstabelle ist ein Fehler aufgetreten!';
$_['warning_msg_void_refund_status_update']      = 'Warnung:Bestellung wurde erfolgreich storniert, aber beim Aktualisieren des stornierten Status in die Storno-Rückerstattungstabelle ist ein Fehler aufgetreten!';
$_['warning_msg_webhook_service_request']        = 'Warnung: Ihre Anfrage zur Erstellung eines Webhook-Abonnements konnte nicht verarbeitet werden.';
