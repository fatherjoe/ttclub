<?php defined( '_JEXEC' ) or die( 'Restricted access' ); 
 
class TtclubViewsTeamsJson extends JViewHtml
{
  function render()
  {
    $app = JFactory::getApplication();
    $type = $app->input->get('type');
    $id = $app->input->get('id');
    $view = $app->input->get('view');
 
    //retrieve task list from model
    $model = new TtclubModelsTeams();

    $this->teams = $model->getTeams($id,$view,FALSE);

    // Get the document object.
    $document =& JFactory::getDocument();

    // Set the MIME type for JSON output.
    //$document->setMimeEncoding('application/json');

    // Output the JSON data.
    echo json_encode($this->teams);

    // display
    return parent::render();
  } 
}