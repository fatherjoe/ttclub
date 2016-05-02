<?php defined( '_JEXEC' ) or die( 'Restricted access' );
JHTML::script('http://ajax.googleapis.com/ajax/libs/angularjs/1.3.8/angular.js');
JHTML::script('https://cdnjs.cloudflare.com/ajax/libs/restangular/1.4.0/restangular.js');
JHTML::script('https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.7.0/underscore.js');
JHtml::script(Juri::base() . 'components/com_ttclub/assets/js/app.js');

class TtclubViewsTeamsHtml extends JViewHtml
{
  function render()
  {
    $app = JFactory::getApplication();
    $type = $app->input->get('type');
    $id = $app->input->get('id');
    $view = $app->input->get('view');
    $this->setLayout('teamlist');
 
    //retrieve task list from model
    $model = new TtclubModelsTeams();

    $this->team = $model->getTeam($id,$view,FALSE);

    //display
    return parent::render();
  } 
}