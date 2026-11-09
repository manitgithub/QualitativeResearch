    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.8/angular.min.js" ></script>

<?php // $this->layout = 'noTheme';?>

	
<div ng-app="myApp" ng-controller="myCtrl">
          
               
                <script>
                var app = angular.module('myApp', []);
                app.controller('myCtrl', function($scope,$http) {
                    $scope.name = "John Doe";
					$http.get("index.php?r=site/page&view=json1&id=<?php echo $_GET[id];?>").success(function (response) {$scope.driver = response.drivers; });
					
                });
                </script>

                            <div class="panel-body">
                         
 <label>สืบค้าจากข้อมูลทั้งหมด: <input ng-model="search.$" style="width:300px;"></label> <br>
<label>ผู้ตอบ <input ng-model="search.text2" style="width:300px;"></label><br>
<label>คำถาม <input ng-model="search.text" style="width:300px;"></label><br>
<label>คำตอบ <input ng-model="search.value" style="width:300px;"></label><br>
<label>ตีความ <input ng-model="search.name" style="width:300px;"></label><br>

                            
<div class="row">
	<div class="col-md-12" ng-repeat="user in driver | filter:search:strict">
           <div class="box box-success">
            <div class="box-body">
                              <strong></strong> 
                              <div><strong>ผู้ตอบ</strong> : {{user.text2}}</div>
                              <div><strong>คำถาม</strong> : {{user.text}}</div>
                              <!--<div><strong>รายละเอียด</strong> : {{user.detail}}</div>
                              <div><strong>แจ้งไปยัง</strong> : {{user.type}}</div>-->
                              <div><strong>คำตอบ : </strong>{{user.value}}</div>
                              <div><strong>ตีความ : </strong>{{user.name}}</div>
                              <div>
                              </div>
                              
                              
                              
                              
                                    
								
                              </div>
	</div>
	</div>                   
    
