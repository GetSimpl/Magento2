<?php

namespace Simpl\Splitpay\Plugin;

class CsrfValidatorSkip
{
    /**
     * @param $subject
     * @param \Closure $proceed
     * @param $request
     * @param $action
     * @return void
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    )
    {
        if ($request->getModuleName() == 'splitpay') {
            return;
        }

        $proceed($request, $action);
    }
}
