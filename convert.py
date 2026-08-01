import csv

# 1. Open your exported file (Make sure you save your Excel sheet as 'vouchers.csv' first!)
csv_file_name = 'vouchers.csv'
sql_file_name = 'import_vouchers.sql'

print("🔄 Converting your Guangri NMS spreadsheet data rows...")

try:
    with open(csv_file_name, mode='r', encoding='utf-8-sig') as csv_file:
        # Use DictReader to capture column headers cleanly
        reader = csv.DictReader(csv_file)
        
        sql_statements = ["USE railway;\n", "INSERT INTO wifi_vouchers (voucher_code, price_tier, status) VALUES\n"]
        rows_list = []
        
        for row in reader:
            # Match the exact column titles from your spreadsheet image
            pin_code = row['PIN Code'].strip()
            face_value = row['Face Value'].strip()
            
            # Formulate the SQL row block
            rows_list.append(f"('{pin_code}', {face_value}, 'AVAILABLE')")
        
        # Join rows with commas, and close the absolute last line with a semicolon!
        sql_statements.append(",\n".join(rows_list) + ";\n")
        
    # 2. Write the formatted statements out into your final SQL file
    with open(sql_file_name, mode='w', encoding='utf-8') as sql_file:
        sql_file.writelines(sql_statements)
        
    print(f"✅ Success! Your file '{sql_file_name}' has been created with {len(rows_list)} vouchers!")

except Exception as e:
    print(f"❌ Error reading file: {e}")
