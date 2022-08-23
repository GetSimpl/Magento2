<?php
namespace Simpl\Splitpay\Plugin;

class CsrfValidatorSkip
{
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        if ($request->getModuleName() == 'splitpay') {
            return;
        }
        
        $proceed($request, $action);
    }
}
