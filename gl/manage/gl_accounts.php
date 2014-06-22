<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = 'SA_GLACCOUNT';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");
 
page(_($help_context = "Chart of Accounts"));

include($path_to_root . "/includes/ui.inc");
include($path_to_root . "/gl/includes/gl_db.inc");
include($path_to_root . "/admin/db/tags_db.inc");
include_once($path_to_root . "/includes/data_checks.inc");

check_db_has_gl_account_groups(_("There are no account groups defined. Please define at least one account group before entering accounts."));


$temp_db_config = $db_connections[0];
$temp_db_name = $temp_db_config["dbname"];
// check MNO_CLASSIFICATION column exists in CHART_MASTER table
if (!check_empty_result("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$temp_db_name}' AND TABLE_NAME='".TB_PREF."chart_master' AND COLUMN_NAME='mno_classification'"))
{
    $result = db_query("ALTER TABLE ".TB_PREF."chart_master ADD `mno_classification` VARCHAR( 510 ) NOT NULL;");
}

//-------------------------------------------------------------------------------------

if (isset($_POST['_AccountList_update'])) 
{
	$_POST['selected_account'] = $_POST['AccountList'];
	unset($_POST['account_code']);
}

if (isset($_POST['selected_account']))
{
	$selected_account = $_POST['selected_account'];
} 
elseif (isset($_GET['selected_account']))
{
	$selected_account = $_GET['selected_account'];
}
else
	$selected_account = "";
//-------------------------------------------------------------------------------------

if (isset($_POST['add']) || isset($_POST['update'])) 
{

	$input_error = 0;

	if (strlen(trim($_POST['account_code'])) == 0) 
	{
		$input_error = 1;
		display_error( _("The account code must be entered."));
		set_focus('account_code');
	} 
	elseif (strlen(trim($_POST['account_name'])) == 0) 
	{
		$input_error = 1;
		display_error( _("The account name cannot be empty."));
		set_focus('account_name');
	} 
	/*elseif (!$accounts_alpha && !is_numeric($_POST['account_code'])) 
	{
	    $input_error = 1;
	    display_error( _("The account code must be numeric."));
		set_focus('account_code');
	}*/
	if ($input_error != 1)
	{
		if ($accounts_alpha == 2)
			$_POST['account_code'] = strtoupper($_POST['account_code']);

		if (!isset($_POST['account_tags']))
			$_POST['account_tags'] = array();

    	if ($selected_account) 
		{
			if (get_post('inactive') == 1 && is_bank_account($_POST['account_code']))
			{
				display_error(_("The account belongs to a bank account and cannot be inactivated."));
			}	
                        elseif (update_gl_account($_POST['account_code'], $_POST['account_name'], 
				$_POST['account_type'], $_POST['account_code2'], $_POST['mno_classification'])) 
                        {
				update_record_status($_POST['account_code'], $_POST['inactive'],
					'chart_master', 'account_code');
				update_tag_associations(TAG_ACCOUNT, $_POST['account_code'], 
					$_POST['account_tags']);
				$Ajax->activate('account_code'); // in case of status change
				display_notification(_("Account data has been updated."));
                                mno_hook_push_account($_POST['account_code']);
			}
		}
    	else 
		{
                    if (add_gl_account($_POST['account_code'], $_POST['account_name'], 
                                    $_POST['account_type'], $_POST['account_code2'], $_POST['mno_classification']))
                    {
                                add_tag_associations($_POST['account_code'], $_POST['account_tags']);
                                display_notification(_("New account has been added."));
                                $selected_account = $_POST['AccountList'] = $_POST['account_code'];
                                mno_hook_push_account($selected_account);
                    }
                    else
                    {
                                display_error(_("Account not added, possible duplicate Account Code."));
                    }
		}
		$Ajax->activate('_page_body');
	}
} 

//-------------------------------------------------------------------------------------

function can_delete($selected_account)
{
	if ($selected_account == "")
		return false;

	if (key_in_foreign_table($selected_account, 'gl_trans', 'account'))
	{
		display_error(_("Cannot delete this account because transactions have been created using this account."));
		return false;
	}

	if (gl_account_in_company_defaults($selected_account))
	{
		display_error(_("Cannot delete this account because it is used as one of the company default GL accounts."));
		return false;
	}

	if (key_in_foreign_table($selected_account, 'bank_accounts', 'account_code'))
	{
		display_error(_("Cannot delete this account because it is used by a bank account."));
		return false;
	}	

	if (gl_account_in_stock_category($selected_account))
	{
		display_error(_("Cannot delete this account because it is used by one or more Item Categories."));
		return false;
	}	
	
	if (gl_account_in_stock_master($selected_account))
	{
		display_error(_("Cannot delete this account because it is used by one or more Items."));
		return false;
	}	
	
	if (gl_account_in_tax_types($selected_account))
	{
		display_error(_("Cannot delete this account because it is used by one or more Taxes."));
		return false;
	}	
	
	if (gl_account_in_cust_branch($selected_account))
	{
		display_error(_("Cannot delete this account because it is used by one or more Customer Branches."));
		return false;
	}		
	
	if (gl_account_in_suppliers($selected_account))
	{
		display_error(_("Cannot delete this account because it is used by one or more suppliers."));
		return false;
	}

	if (gl_account_in_quick_entry_lines($selected_account))
	{
		display_error(_("Cannot delete this account because it is used by one or more Quick Entry Lines."));
		return false;
	}

	return true;
}

//--------------------------------------------------------------------------------------

if (isset($_POST['delete'])) 
{

	if (can_delete($selected_account))
	{
		delete_gl_account($selected_account);
		$selected_account = $_POST['AccountList'] = '';
		delete_tag_associations(TAG_ACCOUNT,$selected_account, true);
		$selected_account = $_POST['AccountList'] = '';
		display_notification(_("Selected account has been deleted"));
		unset($_POST['account_code']);
		$Ajax->activate('_page_body');
	}
} 

$accountTypesOps = array("Asset"=>array(
                                            "Bank"=>array(
                                                                            "ASSET_BANK_CASHONHAND"=>"Cash on hand",
                                                                            "ASSET_BANK_CHECKING"=>"Checking",
                                                                            "ASSET_BANK_MONEYMARKET"=>"Money market",
                                                                            "ASSET_BANK_RENTSHELDINTRUST"=>"Rents held in trust",
                                                                            "ASSET_BANK_SAVINGS"=>"Savings",
                                                                            "ASSET_BANK_TRUSTACCOUNTS"=>"Trust accounts"
                                                         ), 
                                            "Other current asset"=>array(
                                                                            "ASSET_OTHERCURRENTASSET_ALLOWANCEFORBADDEBTS"=>"Allowance for bad debts",
                                                                            "ASSET_OTHERCURRENTASSET_DEVELOPMENTCOSTS"=>"Development costs",
                                                                            "ASSET_OTHERCURRENTASSET_EMPLOYEECASHADVANCES"=>"Employee cash advances",
                                                                            "ASSET_OTHERCURRENTASSET_OTHERCURRENTASSETS"=>"Other current assets",
                                                                            "ASSET_OTHERCURRENTASSET_INVENTORY"=>"Inventory",
                                                                            "ASSET_OTHERCURRENTASSET_INVESTMENT_MORTGAGEREALESTATELOANS"=>"Investment - Mortgage real estate loans",
                                                                            "ASSET_OTHERCURRENTASSET_INVESTMENT_OTHER"=>"Investment - Other",
                                                                            "ASSET_OTHERCURRENTASSET_INVESTMENT_TAXEXEMPTSECURITIES"=>"Investment - Tax exempt securities",
                                                                            "ASSET_OTHERCURRENTASSET_INVESTMENT_USGOVERNMENTOBLIGATIONS"=>"Investment - US government obligations",
                                                                            "ASSET_OTHERCURRENTASSET_LOANSTOOFFICERS"=>"Loans to officers",
                                                                            "ASSET_OTHERCURRENTASSET_LOANSTOOTHERS"=>"Loans to others",
                                                                            "ASSET_OTHERCURRENTASSET_LOANSTOSTOCKHOLDERS"=>"Loans to stockholders",
                                                                            "ASSET_OTHERCURRENTASSET_PREPAIDEXPENSES"=>"Prepaid expenses",
                                                                            "ASSET_OTHERCURRENTASSET_RETAINAGE"=>"Retainage",
                                                                            "ASSET_OTHERCURRENTASSET_UNDEPOSITEDFUNDS"=>"Undeposited Funds"    
                                                                        ), 
                                            "Fixed asset"=>array(
                                                                            "ASSET_FIXEDASSET_ACCUMULATEDDEPLETION"=>"Accumulated depletion",
                                                                            "ASSET_FIXEDASSET_ACCUMULATEDDEPRECIATION"=>"Accumulated depreciation",
                                                                            "ASSET_FIXEDASSET_DEPLETABLEASSETS"=>"Depletable assets",
                                                                            "ASSET_FIXEDASSET_FURNITUREANDFIXTURES"=>"Furniture and fixtures",
                                                                            "ASSET_FIXEDASSET_LAND"=>"Land",
                                                                            "ASSET_FIXEDASSET_LEASEHOLDIMPROVEMENTS"=>"Leasehold improvements",
                                                                            "ASSET_FIXEDASSET_OTHERFIXEDASSETS"=>"Other fixed assets",
                                                                            "ASSET_FIXEDASSET_ACCUMULATEDAMORTIZATION"=>"Accumulated amortization",
                                                                            "ASSET_FIXEDASSET_BUILDINGS"=>"Buildings",
                                                                            "ASSET_FIXEDASSET_INTANGIBLEASSETS"=>"Intangible assets",
                                                                            "ASSET_FIXEDASSET_MACHINERYANDEQUIPMENT"=>"Machinery and equipment",
                                                                            "ASSET_FIXEDASSET_VEHICLES"=>"Vehicles"
                                                                ), 
                                            "Other asset"=>array(
                                                                            "ASSET_OTHERASSET_LEASEBUYOUT"=>"Lease Buyout",
                                                                            "ASSET_OTHERASSET_OTHERLONGTERMASSETS"=>"Other long term assets",
                                                                            "ASSET_OTHERASSET_SECURITYDEPOSITS"=>"Security deposits",
                                                                            "ASSET_OTHERASSET_ACCUMULATEDAMORTIZATIONOFOTHERASSETS"=>"Accumulated amortization of other assets",
                                                                            "ASSET_OTHERASSET_GOODWILL"=>"Goodwill",
                                                                            "ASSET_OTHERASSET_LICENSES"=>"Licenses",
                                                                            "ASSET_OTHERASSET_ORGANIZATIONALCOSTS"=>"Organizational costs"
                                                                ), 
                                            "Accounts receivable"=>array(
                                                                            "ASSET_ACCOUNTSRECEIVABLE_ACCOUNTSRECEIVABLE"=>"Accounts receivable"
                                                                        )
                                       ),
                         "Equity"=>array(
                                            "Equity"=>array(
                                                                            "EQUITY_EQUITY_OPENINGBALANCEEQUITY"=>"Opening balance equity",
                                                                            "EQUITY_EQUITY_PARTNERSEQUITY"=>"Partners equity",
                                                                            "EQUITY_EQUITY_RETAINEDEARNINGS"=>"Retained earnings",
                                                                            "EQUITY_EQUITY_ACCUMULATEDADJUSTMENT"=>"Accumulated adjustment",
                                                                            "EQUITY_EQUITY_OWNERSEQUITY"=>"Owners equity",
                                                                            "EQUITY_EQUITY_PAIDINCAPITALORSURPLUS"=>"Paid in capital or surplus",
                                                                            "EQUITY_EQUITY_PARTNERCONTRIBUTIONS"=>"Partner contributions",
                                                                            "EQUITY_EQUITY_PARTNERDISTRIBUTIONS"=>"Partner distributions",
                                                                            "EQUITY_EQUITY_PREFERREDSTOCK"=>"Preferred stock",
                                                                            "EQUITY_EQUITY_COMMONSTOCK"=>"Common stock",
                                                                            "EQUITY_EQUITY_TREASURYSTOCK"=>"Treasury stock"
                                                           )
                                        ), 
                         "Expense"=>array (
                                            "Expense"=>array(
                                                                            "EXPENSE_EXPENSE_ADVERTISINGPROMOTIONAL"=>"Advertising promotional",
                                                                            "EXPENSE_EXPENSE_BADDEBTS"=>"Bad debts",
                                                                            "EXPENSE_EXPENSE_BANKCHARGES"=>"Bank charges",
                                                                            "EXPENSE_EXPENSE_CHARITABLECONTRIBUTIONS"=>"Charitable contributions",
                                                                            "EXPENSE_EXPENSE_ENTERTAINMENT"=>"Entertainment",
                                                                            "EXPENSE_EXPENSE_ENTERTAINMENTMEALS"=>"Entertainment meals",
                                                                            "EXPENSE_EXPENSE_EQUIPMENTRENTAL"=>"Equipment rental",
                                                                            "EXPENSE_EXPENSE_GLOBALTAXEXPENSE"=>"Global tax expense",
                                                                            "EXPENSE_EXPENSE_INSURANCE"=>"Insurance",
                                                                            "EXPENSE_EXPENSE_INTERESTPAID"=>"Interest paid",
                                                                            "EXPENSE_EXPENSE_LEGALPROFESSIONALFEES"=>"Legal professional fees",
                                                                            "EXPENSE_EXPENSE_OFFICEGENERALADMINISTRATIVEEXPENSES"=>"Office general administrative expenses",
                                                                            "EXPENSE_EXPENSE_OTHERMISCELLANEOUSSERVICECOST"=>"Other miscellaneous service cost",
                                                                            "EXPENSE_EXPENSE_PROMOTIONALMEALS"=>"Promotional meals",
                                                                            "EXPENSE_EXPENSE_RENTORLEASEOFBUILDINGS"=>"Rent or lease of buildings",
                                                                            "EXPENSE_EXPENSE_REPAIRMAINTENANCE"=>"Repair maintenance",
                                                                            "EXPENSE_EXPENSE_SHIPPINGFREIGHTDELIVERY"=>"Shipping freight delivery",
                                                                            "EXPENSE_EXPENSE_SUPPLIESMATERIALS"=>"Supplies materials",
                                                                            "EXPENSE_EXPENSE_TRAVEL"=>"Travel",
                                                                            "EXPENSE_EXPENSE_TRAVELMEALS"=>"Travel meals",
                                                                            "EXPENSE_EXPENSE_UTILITIES"=>"Utilities",
                                                                            "EXPENSE_EXPENSE_AUTO"=>"Auto",
                                                                            "EXPENSE_EXPENSE_COSTOFLABOR"=>"Cost of labor",
                                                                            "EXPENSE_EXPENSE_DUESSUBSCRIPTIONS"=>"Dues subscriptions",
                                                                            "EXPENSE_EXPENSE_PAYROLL_EXPENSES"=>"Payroll expenses",
                                                                            "EXPENSE_EXPENSE_TAXESPAID"=>"Taxes paid"
                                                            ),
                                            "Other expense"=>array(
                                                                            "EXPENSE_OTHEREXPENSE_DEPRECIATION"=>"Depreciation",
                                                                            "EXPENSE_OTHEREXPENSE_EXCHANGEGAINORLOSS"=>"Exchange gain or loss",
                                                                            "EXPENSE_OTHEREXPENSE_OTHERMISCELLANEOUSEXPENSE"=>"Other miscellaneous expense",
                                                                            "EXPENSE_OTHEREXPENSE_PENALTIESSETTLEMENTS"=>"Penalties settlements",
                                                                            "EXPENSE_OTHEREXPENSE_AMORTIZATION"=>"Amortization"
                                                                  ),
                                            "Cost of goods sold"=>array(
                                                                            "EXPENSE_COSTOFGOODSSOLD_EQUIPMENTRENTALCOS"=>"Equipment rental cost of sales",
                                                                            "EXPENSE_COSTOFGOODSSOLD_OTHERCOSTSOFSERVICECOS"=>"Other costs of service cost of sales",
                                                                            "EXPENSE_COSTOFGOODSSOLD_SHIPPINGFREIGHTDELIVERYCOS"=>"Shipping freight delivery cost of sales",
                                                                            "EXPENSE_COSTOFGOODSSOLD_SUPPLIESMATERIALSCOGS"=>"Supplies materials cost of goods sold",
                                                                            "EXPENSE_COSTOFGOODSSOLD_COSTOFLABORCOS"=>"Cost of labor cost of goods sold"
                                                                       )
                                          ),
                         "Liability"=>array (
                                            "Accounts payable"=>array(
                                                                            "LIABILITY_ACCOUNTSPAYABLE_ACCOUNTSPAYABLE"=>"Accounts payable"
                                                                     ),
                                            "Credit card"=>array(
                                                                            "LIABILITY_CREDITCARD_CREDITCARD"=>"Credit card"
                                                                ),
                                            "Long term liability"=>array(
                                                                            "LIABILITY_LONGTERMLIABILITY_NOTESPAYABLE"=>"Notes payable",
                                                                            "LIABILITY_LONGTERMLIABILITY_OTHERLONGTERMLIABILITIES"=>"Other long term liabilities",
                                                                            "LIABILITY_LONGTERMLIABILITY_SHAREHOLDERNOTESPAYABLE"=>"Shareholder notes payable"
                                                                        ),
                                            "Other current liability"=>array(
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_DIRECTDEPOSITPAYABLE"=>"Direct deposit payable",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_LINEOFCREDIT"=>"Line of credit",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_LOANPAYABLE"=>"Loan payable",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_GLOBALTAXPAYABLE"=>"Global tax payable",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_GLOBALTAXSUSPENSE"=>"Global tax suspense",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_OTHERCURRENTLIABILITIES"=>"Other current liabilities",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_PAYROLLCLEARING"=>"Payroll clearing",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_PAYROLLTAXPAYABLE"=>"Payroll tax payable",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_PREPAIDEXPENSESPAYABLE"=>"Prepaid expenses payable",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_RENTSINTRUSTLIABILITY"=>"Rents in trust liability",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_TRUSTACCOUNTSLIABILITIES"=>"Trust accounts liabilities",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_FEDERALINCOMETAXPAYABLE"=>"Federal income tax payable",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_INSURANCEPAYABLE"=>"Insurance payable",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_SALESTAXPAYABLE"=>"Sales tax payable",
                                                                            "LIABILITY_OTHERCURRENTLIABILITY_STATELOCALINCOMETAXPAYABLE"=>"State local income tax payable"
                                                                            )
                                            ), 
                         "Revenue"=>array(
                                            "Income"=>array(
                                                                            "REVENUE_INCOME_NONPROFITINCOME"=>"Non profit income",
                                                                            "REVENUE_INCOME_OTHERPRIMARYINCOME"=>"Other primary income",
                                                                            "REVENUE_INCOME_SALESOFPRODUCTINCOME"=>"Sales of product income",
                                                                            "REVENUE_INCOME_SERVICEFEEINCOME"=>"Service fee income",
                                                                            "REVENUE_INCOME_DISCOUNTSREFUNDSGIVEN"=>"Discounts refunds given"
                                                           ),
                                            "Other income"=>array(
                                                                            "REVENUE_OTHERINCOME_DIVIDENDINCOME"=>"Dividend income",
                                                                            "REVENUE_OTHERINCOME_INTERESTEARNED"=>"Interest earned",
                                                                            "REVENUE_OTHERINCOME_OTHERINVESTMENTINCOME"=>"Other investment income",
                                                                            "REVENUE_OTHERINCOME_OTHERMISCELLANEOUSINCOME"=>"Other miscellaneous income",
                                                                            "REVENUE_OTHERINCOME_TAXEXEMPTINTEREST"=>"Tax exempt interest"
                                                                 )
                                         )
                      );

//-------------------------------------------------------------------------------------

start_form();

if (db_has_gl_accounts()) 
{
	start_table(TABLESTYLE_NOBORDER);
	start_row();
    gl_all_accounts_list_cells(null, 'AccountList', null, false, false,
		_('New account'), true, check_value('show_inactive'));
	check_cells(_("Show inactive:"), 'show_inactive', null, true);
	end_row();
	end_table();
	if (get_post('_show_inactive_update')) {
		$Ajax->activate('AccountList');
		set_focus('AccountList');
	}
}
	
br(1);
start_table(TABLESTYLE2);

if ($selected_account != "") 
{
	//editing an existing account
	$myrow = get_gl_account($selected_account);

	$_POST['account_code'] = $myrow["account_code"];
	$_POST['account_code2'] = $myrow["account_code2"];
	$_POST['account_name']	= $myrow["account_name"];
	$_POST['account_type'] = $myrow["account_type"];
 	$_POST['inactive'] = $myrow["inactive"];
    $_POST['mno_classification'] = $myrow["mno_classification"];
 	
 	$tags_result = get_tags_associated_with_record(TAG_ACCOUNT, $selected_account);
 	$tagids = array();
 	while ($tag = db_fetch($tags_result)) 
 	 	$tagids[] = $tag['id'];
 	$_POST['account_tags'] = $tagids;

	hidden('account_code', $_POST['account_code']);
	hidden('selected_account', $selected_account);
		
	label_row(_("Account Code:"), $_POST['account_code']);
} 
else
{
	if (!isset($_POST['account_code'])) {
		$_POST['account_tags'] = array();
		$_POST['account_code'] = $_POST['account_code2'] = '';
		$_POST['account_name']	= $_POST['account_type'] = '';
 		$_POST['inactive'] = 0;
	}
	text_row_ex(_("Account Code:"), 'account_code', 15);
}

text_row_ex(_("Account Code 2:"), 'account_code2', 15);

text_row_ex(_("Account Name:"), 'account_name', 60);

gl_account_types_list_row(_("Account Group:"), 'account_type', null);

$selected_mno_account_classification = (array_key_exists("mno_classification", $_POST) && !empty($_POST['mno_classification'])) ? $_POST['mno_classification'] : null;

mno_account_classifications_list_row(_("Account Classification:"), 'mno_classification', 'mno_classification', $accountTypesOps, $selected_mno_account_classification);

tag_list_row(_("Account Tags:"), 'account_tags', 5, TAG_ACCOUNT, true);

record_status_list_row(_("Account status:"), 'inactive');
end_table(1);

if ($selected_account == "") 
{
	submit_center('add', _("Add Account"), true, '', 'default');
} 
else 
{
    submit_center_first('update', _("Update Account"), '', 'default');
    // MNO MODIFICATION - DISABLE DELETE OF ACCOUNTS
    //submit_center_last('delete', _("Delete account"), '',true);
}
end_form();

end_page();

?>
