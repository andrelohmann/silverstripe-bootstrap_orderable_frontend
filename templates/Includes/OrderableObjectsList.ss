
    <% if $Objects %>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>ID</th>
                <th>Title</th>
                <th>Edit</th>
            </tr>
        </thead>
        <tbody class="ui-sortable"><%-- this css-class is essential, it wrappes the drag & dropable list --%>
            <% loop $Channels %>
            <tr id="$ID" class="ss-item"><%-- put the Objects ID here, css-class ss-item is essential --%>
                <td class="col-reorder">
                    <span class="handle glyphicon glyphicon-move" aria-hidden="true"></span><% css-classes col-reorder > handle are building the draggable html element --%>
                </td>
                <td>$ID</td>
                <td>$Title</td>
                <td>
                    <a href="yourcontroller/editOrderableObject/{$ID}?BackURL=$Up.URL" class="btn btn-warning btn-block">Edit</a>
                    <%-- BackURL helps you to jump back to the correct page --%>
                </td>
            </tr>
            <% end_loop %>
        </tbody>
    </table>
    <% if $Objects.MoreThanOnePage %>
    <div class="text-center">
        <ul class="pagination"><%-- css-class pagination is essential --%>
            <% if $Objects.NotFirstPage %>
            <li><a class="prev ss-previouspage" href="$Objects.PrevLink">Prev</a></li><%-- css-class ss-previouspage is essential --%>
            <% end_if %>
            <% loop $Objects.PaginationSummary %>
            <% if $CurrentBool %>
            <li class="active "><a href="$Top.URL">$PageNum</a></li>
            <% else %>
            <% if $Link %>
            <li><a href="$Link">$PageNum</a></li>
            <% else %>
            <li><a href="#">...</a></li>
            <% end_if %>
            <% end_if %>
            <% end_loop %>
            <% if $Objects.NotLastPage %>
            <li><a class="next ss-nextpage" href="$Channels.NextLink">Next</a></li><%-- css-class ss-nextpage is essential --%>
            <% end_if %>
        </ul>
    </div>
    <% end_if %> 
    <% else %>
    <p>No Objects available.</p>
    <% end_if %>
