{{#each relationshipsPanels}}

 <div class="panel panel-default panel-{{name}}" >
        <div class="panel-heading">
            <h4 class="panel-title">
              {{label}}
            </h4>
        </div>
     <div class="list" data-panel="{{name}}">
     <table class="table full-table table-striped table-fixed table-scrolled table-bordered" >
             <thead>
             <tr>
                 <th>{{ translate 'instance' scope='Synchronization' }}</th>
                 <th>
                     {{translate 'current' scope='Synchronization' category='labels'}}
                 </th>
                 {{#each ../instanceNames}}
                <th colspan="{{../columnCountCurrent}}" class="text-center">
                    {{name}}
                </th>
                {{/each}}
                 <th width="25"></th>
             </tr>
             </thead>
             <tbody>
                  <tr class="list-row data-id="{{attributeId}}">
                    <td class="cell" colspan="4"> {{translate 'Loading...'}}</td>
                 </tr>
                  <tr class="list-row" >
                    <td class="cell" colspan="4"> {{translate 'Loading...'}}</td>
                 </tr>
             </tbody>
         </table>
</div>
        <div class="panel-scroll hidden">
            <div></div>
        </div>
 </div>
{{/each}}