<?php
class game_addGameRequestForm extends feedback_publicForm {

  public $contentType = 'json';


  function prepare(){

    parent::prepare();

    $customizeCfg = array(
      'url' => array(
        'forward' => '/game/add-game-request',
      ),
      'title' => array(
        'value' => \Verba\Lang::get('game ags addGameForm title'),
      ),
      'fields' => array(
        'department' => array(
          'hidden' => true,
          'value' => 1171,
          'readonly' => true,
        ),
        'additional' => array(
          'attr' => array(
            'placeholder' => \Verba\Lang::get('game ags addGameForm additional placeholder'),
          ),
          'extensions' => array(
            'items' => array(
              'anno' => array(
                'annotation' => \Verba\Lang::get('game ags addGameForm additional anno'),
              )
            )
          )
        ),
        'text' => array(
          'attr' => array(
            'placeholder' => \Verba\Lang::get('game ags addGameForm text placeholder'),
          ),
          'extensions' => array(
            'items' => array(
              'anno' => array(
                'annotation' => \Verba\Lang::get('game ags addGameForm text anno'),
              )
            )
          )
        )
      )
    );

    $this->dcfg = array_replace_recursive($this->dcfg, $customizeCfg);

   }

}
?>
