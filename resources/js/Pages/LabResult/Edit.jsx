import { useForm, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function EditLabResult({ labResult, patients, tests, employees }) {
    const { data, setData, put, processing, errors } = useForm({
        patient_id: labResult.patient_id || '',
        test_id: labResult.test_id || '',
        result_date: labResult.result_date ? labResult.result_date.split('T')[0] : '',
        findings: labResult.findings || '',
        normal_range: labResult.normal_range || '',
        result_value: labResult.result_value || '',
        remarks: labResult.remarks || '',
        performed_by: labResult.performed_by || '',
        verified_by: labResult.verified_by || '',
        status: labResult.status || 'draft',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/lab-results/${labResult.lab_result_id}`);
    };

    return (
        <AppLayout title="Edit Lab Result">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-8">
                    <div className="mb-6 flex items-center justify-between">
                        <h2 className="text-2xl font-semibold text-gray-800">Edit Lab Result</h2>
                        <button
                            onClick={() => router.get('/lab-results')}
                            className="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 font-medium"
                        >
                            Back to List
                        </button>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Patient *</label>
                                <select
                                    value={data.patient_id}
                                    onChange={(e) => setData('patient_id', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    required
                                >
                                    <option value="">Select Patient</option>
                                    {patients.map(patient => (
                                        <option key={patient.patient_id} value={patient.patient_id}>
                                            {patient.lastname}, {patient.firstname}
                                        </option>
                                    ))}
                                </select>
                                {errors.patient_id && <p className="mt-1 text-sm text-red-600">{errors.patient_id}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Test *</label>
                                <select
                                    value={data.test_id}
                                    onChange={(e) => setData('test_id', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    required
                                >
                                    <option value="">Select Test</option>
                                    {tests.map(test => (
                                        <option key={test.test_id} value={test.test_id}>
                                            {test.label}
                                        </option>
                                    ))}
                                </select>
                                {errors.test_id && <p className="mt-1 text-sm text-red-600">{errors.test_id}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Result Date</label>
                                <input
                                    type="date"
                                    value={data.result_date}
                                    onChange={(e) => setData('result_date', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Result Value</label>
                                <input
                                    type="text"
                                    value={data.result_value}
                                    onChange={(e) => setData('result_value', e.target.value)}
                                    placeholder="e.g., 120 mg/dL"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Normal Range</label>
                                <input
                                    type="text"
                                    value={data.normal_range}
                                    onChange={(e) => setData('normal_range', e.target.value)}
                                    placeholder="e.g., 70-100 mg/dL"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                                <select
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    required
                                >
                                    <option value="draft">Draft</option>
                                    <option value="final">Final</option>
                                    <option value="revised">Revised</option>
                                </select>
                            </div>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Findings</label>
                                <textarea
                                    value={data.findings}
                                    onChange={(e) => setData('findings', e.target.value)}
                                    rows="3"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                                <textarea
                                    value={data.remarks}
                                    onChange={(e) => setData('remarks', e.target.value)}
                                    rows="3"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Performed By</label>
                                <select
                                    value={data.performed_by}
                                    onChange={(e) => setData('performed_by', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                >
                                    <option value="">Select Employee</option>
                                    {employees.map(employee => (
                                        <option key={employee.employee_id} value={employee.employee_id}>
                                            {employee.lastname}, {employee.firstname}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Verified By</label>
                                <select
                                    value={data.verified_by}
                                    onChange={(e) => setData('verified_by', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                >
                                    <option value="">Select Employee</option>
                                    {employees.map(employee => (
                                        <option key={employee.employee_id} value={employee.employee_id}>
                                            {employee.lastname}, {employee.firstname}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <div className="flex gap-4">
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-6 py-2 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg hover:from-pink-600 hover:to-pink-700 font-medium disabled:opacity-50"
                            >
                                Update Lab Result
                            </button>
                            <button
                                type="button"
                                onClick={() => router.get('/lab-results')}
                                className="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 font-medium"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
