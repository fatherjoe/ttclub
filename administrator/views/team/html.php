<?php defined( '_JEXEC' ) or die( 'Restricted access' ); 
 
class TtClubViewsTeamHtml extends JViewHtml
{
  function render()
  {
    $app = JFactory::getApplication();
    $type = $app->input->get('type');
    $id = $app->input->get('id');
    $view = $app->input->get('view');
 
    //retrieve task list from model
    $model = new TtClubModelsTeam();

    $this->book = $model->getTeam($id,$view,FALSE);
    
    //display
    return parent::render();
  } 
}