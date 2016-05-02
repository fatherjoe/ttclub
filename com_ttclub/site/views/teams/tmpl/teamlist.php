<?php defined('_JEXEC') or die('Restricted access');?>
<div ng-app="ttclubApp">
    <div ng-controller="TeamCtrl">
        <ul>
            <li ng-repeat="team in teams">
                <span>{{team.id}}</span>
                <p>{{team.name}}</p>
            </li>
        </ul>
    </div>
</div>
