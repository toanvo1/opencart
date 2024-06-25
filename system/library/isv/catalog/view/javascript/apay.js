$(document).ready(function () {
  mutationObserverCustomFunction(
    document.querySelector("#collapse-payment-method"),
    { subtree: true, childList: true },
    applePayPaymentMethodHide
  );
});

$(document).ready(function () {
  mutationObserverCustomFunction(
    document.querySelector("#collapse-checkout-confirm"),
    { subtree: true, childList: true },
    applePayPaymentMethodHide
  );
});

const applePayPaymentMethodHide = () => {
  if (!window.ApplePaySession) {
    $("input[value=cybersource_apay]").parent().remove();
  } else if (window.ApplePaySession && document.querySelector("input[value=cybersource_apay]").checked) {
    checkForAddressMatch();
  }
};

function mutationObserverCustomFunction(targetNode, config, callback) {
  try {
    if (MutationObserver) {
      const observer = new MutationObserver(callback);
      if (targetNode && targetNode.nodeType === 1) {
        observer.observe(targetNode, config);
      }
    }
  } catch (exception) {}
}
