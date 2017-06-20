function resetStock()
{
	var fso = new ActiveXObject("Scripting.FileSystemObject");
	var fileLoc = '../bd/stock.csv';
	var file = fso.CreateTextFile(fileLoc, true);
	file.writeline('NOMBRE,TIPO,PRECIO (€),STOCK');
	file.Close();
	alert('File created successfully at location: ' + fileLoc);
}