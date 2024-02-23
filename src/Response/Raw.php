<?php
 namespace Verba\Response;

class Raw extends \Verba\Response {

    function build()
    {
        try{
            $this->content = $this->implodeItemsContent();
        }catch(\Verba\Exception\Building $e){
            throw $e;
        }catch(\Exception $e){
            $this->handleException($e);
        }

        return $this->content;
    }
}
