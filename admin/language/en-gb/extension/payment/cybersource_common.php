<?php

/*
 * English
 *
 */
// Help messages
$_['help_configure']                             = 'Enter configuration details in Cybersource Configuration Module';
$_['help_configure_info']                        = 'You can update Configuration details in Cybersource Configuration Module';

// Text messages
$_['text_module']                                = 'Modules';
$_['text_extension']                             = 'Extensions';
$_['text_button_save']                           = 'Save';
$_['text_button_cancel']                         = 'Cancel';
$_['text_enabled']                               = 'Enable';
$_['text_disabled']                              = 'Disable';
$_['text_authorization_details']                 = 'Authorization Details';
$_['text_transaction_id']                        = 'Transaction ID';
$_['text_settlement_details']                    = 'Settlement Details';
$_['text_transaction_id_s']                      = "Transaction ID's";
$_['text_capture_details']                       = 'Capture Details';
$_['text_auth_reversal_details']                 = 'Auth Reversal Details';
$_['text_refund_details']                        = 'Refund Details';
$_['text_void_capture_details']                  = 'Void Capture Details';
$_['text_void_refund_details']                   = 'Void Refund Details';
$_['text_payment_method']                        = 'Payment Method';

// Entry messages
$_['entry_sort_order']                           = 'Sort Order';
$_['entry_status']                               = 'Status';

// Error messages
$_['error_permission']                           = 'Warning: You do not have permission to modify payment Cybersource!';
$_['error_null_data']                            = 'Unable to fetch user input or null data.';
$_['error_transaction_not_found']                = 'Transaction details not found!!';

// order_capture content
// Text messages
$_['text_button_partial_capture']                = 'Partial Capture';
$_['text_button_capture']                        = 'Capture';
$_['text_button_auth_reversal']                  = 'Cancel';
$_['text_button_void_capture']                   = 'Void Capture';
$_['text_button_void_refund']                    = 'Void Refund';
$_['text_button_refund']                         = 'Refund';
$_['text_button_partial_refund']                 = 'Partial Refund';
$_['text_partial_capture']                       = 'Proceed with Partial Capture!';
$_['text_capture']                               = 'Do you really want to capture the entire order?';
$_['text_auth_reversal']                         = 'Do you really want to cancel the entire order?';
$_['text_void_capture']                          = 'Do you really want to void the captured order?';
$_['text_refund']                                = 'Do you really want to refund the entire order?';
$_['text_void_refund']                           = 'Do you really want to void the entire order?';
$_['text_order_management']                      = 'Order Management';
$_['text_partial_refund']                        = 'Proceed with Refund!';
$_['text_include_shipping_cost']                 = 'Include shipping cost';
$_['text_yes']                                   = 'Yes';
$_['text_no']                                    = 'No';

// Colunm Data
$_['column_order_id']                            = 'Order ID';
$_['column_total']                               = 'Total';
$_['column_product']                             = 'Product';
$_['column_model']                               = 'Model';
$_['column_quantity']                            = 'Quantity';
$_['column_capture']                             = 'Capture';
$_['column_price']                               = 'Price';
$_['column_refund']                              = 'Refund';
$_['column_basePrice']                           = 'Base Price';
$_['column_capture_quantity']                    = 'Quantity to be captured';
$_['column_refund_quantity']                     = 'Quantity to be refunded';

// Error messages
$_['error_capture_quantity']                     = 'Please enter valid quantity';

// Messages
// success messages
$_['success_msg_capture']                        = 'Success:A standard capture was successfully created.';
$_['success_msg_partial_capture']                = 'Success:A partial capture was successfully created.';
$_['success_msg_void_capture']                   = 'Success:A void capture was successfully created.';
$_['success_msg_refund']                         = 'Success:Refund was successfully generated.';
$_['success_msg_void_refund']                    = 'Success:A void refund was successfully created.';
$_['success_msg_partial_void_refund']            = 'Success:A partial void refund was successfully created.';
$_['success_msg_auth_reversal']                  = 'Success:Entire order was successfully cancelled.';
$_['success_msg_refund_cancelled']               = 'Success:Entire refund was successfully cancelled.';

// error messages
$_['error_msg_capture']                          = 'Error:Failed to capture the order.';
$_['error_msg_order_details']                    = 'Error:An error occurred while trying to fetch some of the order details. Please try again.';
$_['error_msg_auth_reversal']                    = 'Error:Failed to cancel the order.';
$_['error_msg_void_capture']                     = 'Error:Failed to void the capture.';
$_['error_msg_refund']                           = 'Error:Failed to refund the order.';
$_['error_msg_void_refund']                      = 'Error:Failed to void the refund.';
$_['error_msg_data_not_found']                   = 'Error:Order details are not found!!. Please try again.';
$_['error_msg_try_again']                        = 'Unable to process your request. Please try again after sometime.';
$_['error_shipping_cost_select']                 = 'Shipping cost must be associated with an item.';
$_['error_refund_shipping_select']               = 'Include shipping cost to proceed with refund.';

// warning messages
$_['warning_msg_capture_insertion']              = 'Warning:Order was successfully captured but an error occurred while inserting capture details into capture table!';
$_['warning_msg_shipping_insertion']             = 'Warning:Order was successfully captured but an error occurred while inserting shipping details into capture table!';
$_['warning_msg_capture_status_update']          = 'Warning:Order was successfully captured but an error occurred while updating capture status into capture table!';
$_['warning_msg_cancel_completed']               = 'Warning:Order was already cancelled!';
$_['warning_msg_auth_reversal_insertion']        = 'Warning:Order was successfully cancelled but an error occurred while inserting auth reversal details into auth reversal table!';
$_['warning_msg_void_capture']                   = 'Warning:Order was successfully voided but an error occurred while inserting void details into void capture table!';
$_['warning_msg_void_capture_status_update']     = 'Warning:Order was successfully voided but an error occurred while updating void capture status into capture table!';
$_['warning_msg_refund_insertion']               = 'Warning:Order was successfully voided but an error occurred while inserting refund details into refund table!';
$_['warning_msg_refund_capture_status_update']   = 'Warning:Order was successfully refunded but an error occurred while updating refund status into capture table!';
$_['warning_msg_refund_status_update']           = 'Warning:Order was successfully refunded but an error occurred while updating refund status into refund table!';
$_['warning_msg_refund_auth_status_update']      = 'Warning:Order was successfully refunded but an error occurred while updating refund details into order table!';
$_['warning_msg_void_refund_insertion']          = 'Warning:Order was successfully voided but an error occurred while inserting void details into void refund table!';
$_['warning_msg_void_refund_status_update']      = 'Warning:Order was successfully voided but an error occurred while updating voided status into void refund table!';
$_['warning_msg_webhook_service_request']        = 'Warning: Unable to process your webhook subscription creation request.';
