<table border="0" cellspacing="0" style="width: 100%; color: #303133;">
    <tbody>
        <tr>
            <td colspan="9" valign="center" align="center" height="60px">租金</td>
        </tr>
        <tr>
            <td colspan="3" valign="center" align="left" height="30px">商铺号：{{$exportData['shop_number']}}</td>
            <td colspan="3" valign="center" align="left" height="30px">租赁合同编号：{{$exportData['contract_number']}}</td>
            <td colspan="2" valign="center" align="left" height="30px">租赁年限：{{$exportData['lease_year']}}</td>
            <td colspan="1" valign="center" align="left" height="30px">是否递增</td>
        </tr>
        <tr>
            <td colspan="3" valign="center" align="left" height="30px">租赁面积：{{$exportData['lease_area']}}</td>
            <td colspan="3" valign="center" align="left" height="30px">装修期：{{$exportData['repair_period']}}</td>
            <td colspan="3" valign="center" align="left" height="30px">业态/品类：{{$exportData['category']}}</td>
        </tr>
        <tr>
            <td colspan="3" valign="center" align="left" height="30px">起始租期：{{$exportData['begin_lease_date']}}</td>
            <td colspan="3" valign="center" align="left" height="30px">计租日期：{{$exportData['stat_lease_date']}}</td>
            <td colspan="3" valign="center" align="left" height="30px">免租经营期：{{$exportData['free_period']}}</td>
        </tr>
        <tr>
            <td colspan="3" valign="center" align="left" height="30px">履约保证金：{{$exportData['performance_bond']}}</td>
            <td colspan="3" valign="center" align="left" height="30px">租金支付方式：{{$exportData['pay_method_name']}}</td>
            <td colspan="3" valign="center" align="left" height="30px">{{$exportData['pay_method'] == -1 ? '租金计租日：' : ''}}{{$exportData['pay_method'] == -1 ? $exportData['rent_day'] : ''}}</td>
        </tr>
        <tr>
            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2" valign="center" align="center" height="30px">期限</td>
            <td colspan="2" valign="center" align="center" height="30px">租赁面积㎡</td>
            <td colspan="2" valign="center" align="center" height="30px">租金单价元/㎡/日(月)</td>
            <td colspan="2" valign="center" align="center" height="30px">年租金</td>
            <td colspan="1" valign="center" align="center" height="30px">{{$exportData['increase_type_name']}}</td>
        </tr>
        @foreach($exportData['detail_list'] as $k => $item)
            <tr>
                <td colspan="2" valign="center" align="center" height="30px">{{$item['year']}}</td>
                <td colspan="2" valign="center" align="center" height="30px">{{$item['area']}}</td>
                <td colspan="2" valign="center" align="center" height="30px">{{$item['price']}}({{$item['month_price']}})</td>
                <td colspan="2" valign="center" align="center" height="30px">{{$item['year_price']}}</td>
                <td colspan="1" valign="center" align="center" height="30px">{{$item['increase']}}</td>
            </tr>
        @endforeach
        <tr>
            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
        </tr>
        <tr>
            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
        </tr>
        <tr>
            <td colspan="9" valign="center" align="center" height="60px">物业费</td>
        </tr>
        <tr>
            <td colspan="3" valign="center" align="left" height="30px">商铺号：{{$exportData['shop_number']}}</td>
            <td colspan="3" valign="center" align="left" height="30px">物业合同编号：{{$exportData['property_contract_number']}}</td>
            <td colspan="3" valign="center" align="left" height="30px">安全责任人：{{$exportData['property_safety_person']}}</td>
        </tr>
        <tr>
            <td colspan="3" valign="center" align="left" height="30px">联系方式：{{$exportData['property_contact_info']}}</td>
            <td colspan="3" valign="center" align="left" height="30px">物业费支付方式：{{$exportData['property_pay_method_name']}}</td>
            <td colspan="3" valign="center" align="left" height="30px">{{$exportData['property_pay_method'] == -1 ? '物业计租日：' : ''}}{{$exportData['property_pay_method'] == -1 ? $exportData['property_rent_day'] : ''}}</td>
        </tr>
        <tr>
            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2" valign="center" align="center" height="30px">期限</td>
            <td colspan="2" valign="center" align="center" height="30px">租赁面积㎡</td>
            <td colspan="2" valign="center" align="center" height="30px">物业费单价元/㎡/月</td>
            <td colspan="2" valign="center" align="center" height="30px">年物业费</td>
            <td colspan="1" valign="center" align="center" height="30px">{{$exportData['property_increase_type_name']}}</td>
        </tr>
        @foreach($exportData['property_detail_list'] as $k => $item)
            <tr>
                <td colspan="2" valign="center" align="center" height="30px">{{$item['year']}}</td>
                <td colspan="2" valign="center" align="center" height="30px">{{$item['area']}}</td>
                <td colspan="2" valign="center" align="center" height="30px">{{$item['price']}}</td>
                <td colspan="2" valign="center" align="center" height="30px">{{$item['year_price']}}</td>
                <td colspan="1" valign="center" align="center" height="30px">{{$item['increase']}}</td>
            </tr>
        @endforeach
    </tbody>
</table>
