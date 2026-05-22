$count = Branch::where('business_id', business()->id)->count();

if (!checkPackageLimit('branches', $count)) {

    return back()->with('error', 'Branch limit exceeded');
}