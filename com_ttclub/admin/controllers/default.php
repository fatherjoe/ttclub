<?php defined( '_JEXEC' ) or die( 'Restricted access' ); 
 
class TtClubControllersDefault extends JControllerBase
{
  public function execute()
  {
    // Get the application
    $app = $this->getApplication();
    //echo time();
	//return;
    // Get the document object.
    echo time();

    $document     = JFactory::getDocument();
 
    $viewName     = $app->input->getWord('view', 'team');
    $viewFormat   = $document->getType();
    $layoutName   = $app->input->getWord('layout', 'default');
 
    $app->input->set('view', $viewName);
 
    // Register the layout paths for the view
    $paths = new SplPriorityQueue;
    $paths->insert(JPATH_COMPONENT . '/views/' . $viewName . '/tmpl', 'normal');
 
    $viewClass  = 'TtClubViews' . ucfirst($viewName) . ucfirst($viewFormat);
    $modelClass = 'TtClubModels' . ucfirst($viewName);
 
    if (false === class_exists($modelClass))
    {
      $modelClass = 'TtClubModelsDefault';
    }
 
    $view = new $viewClass(new $modelClass, $paths);

    $view->setLayout($layoutName);

    // Render our view.
    echo $view->render();
 
    return true;
  }

}