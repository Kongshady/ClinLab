<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Patient</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #495057;
            font-weight: 500;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #007bff;
        }
        .error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
        .btn {
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            cursor: pointer;
            border: none;
            font-size: 14px;
            margin-right: 10px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .form-actions {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New Patient</h1>
        
        <form action="{{ route('patients.store') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="patient_type">Patient Type <span style="color: red;">*</span></label>
                <select name="patient_type" id="patient_type" required>
                    <option value="">Select Type</option>
                    <option value="Internal" {{ old('patient_type') == 'Internal' ? 'selected' : '' }}>Internal</option>
                    <option value="External" {{ old('patient_type') == 'External' ? 'selected' : '' }}>External</option>
                </select>
                @error('patient_type')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="firstname">First Name <span style="color: red;">*</span></label>
                <input type="text" name="firstname" id="firstname" value="{{ old('firstname') }}" required maxlength="50">
                @error('firstname')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="middlename">Middle Name</label>
                <input type="text" name="middlename" id="middlename" value="{{ old('middlename') }}" maxlength="50">
                @error('middlename')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="lastname">Last Name <span style="color: red;">*</span></label>
                <input type="text" name="lastname" id="lastname" value="{{ old('lastname') }}" required maxlength="50">
                @error('lastname')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="birthdate">Birthdate <span style="color: red;">*</span></label>
                <input type="date" name="birthdate" id="birthdate" value="{{ old('birthdate') }}" required>
                @error('birthdate')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="gender">Gender <span style="color: red;">*</span></label>
                <select name="gender" id="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                </select>
                @error('gender')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}" maxlength="20">
                @error('contact_number')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea name="address" id="address" rows="3" maxlength="200">{{ old('address') }}</textarea>
                @error('address')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Patient</button>
                <a href="{{ route('patients.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
