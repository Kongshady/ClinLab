import { useForm, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { useState } from 'react';

export default function LabResults({ labResults, patients, tests, employees, flash }) {
    const [flashMessage, setFlashMessage] = useState(flash?.success);
    const [searchTerm, setSearchTerm] = useState('');
    
    const { data, setData, post, processing, errors, reset } = useForm({
        patient_id: '',
        test_id: '',
        result_date: new Date().toISOString().split('T')[0],
        findings: '',
        normal_range: '',
        result_value: '',
        remarks: '',
        performed_by: '',
        verified_by: '',
        status: 'draft',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/lab-results', {
            onSuccess: () => {
                reset();
                setFlashMessage('Lab result added successfully.');
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this lab result?')) {
            router.delete(`/lab-results/${id}`, {
                onSuccess: () => setFlashMessage('Lab result deleted successfully.')
            });
        }
    };

    const filteredResults = labResults.data.filter(result => {
        const searchLower = searchTerm.toLowerCase();
        const patientName = result.patient ? `${result.patient.firstname} ${result.patient.lastname}`.toLowerCase() : '';
        const testName = result.test ? result.test.label.toLowerCase() : '';
        return patientName.includes(searchLower) || testName.includes(searchLower);
    });

    return (
        <AppLayout title="Lab Results Management">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-8">
                    {flashMessage && (
                        <div className="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                            {flashMessage}
                        </div>
                    )}

                    {/* Add New Lab Result Form */}
                    <div className="mb-8">
                        <h2 className="text-xl font-semibold text-gray-800 mb-4">Add New Lab Result</h2>
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
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
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
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-6 py-2 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg hover:from-pink-600 hover:to-pink-700 font-medium disabled:opacity-50"
                            >
                                Add Lab Result
                            </button>
                        </form>
                    </div>

                    {/* Search Bar */}
                    <div className="mb-6">
                        <label className="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Search by patient name or test..."
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                        />
                    </div>

                    {/* Lab Results List */}
                    <div>
                        <h3 className="text-lg font-semibold text-gray-800 mb-4">Lab Results List</h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Patient</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Test</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Result Value</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Result Date</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {filteredResults.length > 0 ? filteredResults.map((result) => (
                                        <tr key={result.lab_result_id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 text-sm text-gray-900">{result.lab_result_id}</td>
                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                {result.patient ? `${result.patient.lastname}, ${result.patient.firstname}` : '-'}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                {result.test ? result.test.label : '-'}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{result.result_value || '-'}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                {result.result_date ? new Date(result.result_date).toLocaleDateString() : '-'}
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                <span className={`px-2 py-1 rounded-full text-xs font-medium ${result.status_badge_class}`}>
                                                    {result.status.charAt(0).toUpperCase() + result.status.slice(1)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm space-x-2">
                                                <button
                                                    onClick={() => router.get(`/lab-results/${result.lab_result_id}/edit`)}
                                                    className="px-4 py-1.5 bg-orange-500 text-white rounded hover:bg-orange-600 font-medium"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(result.lab_result_id)}
                                                    className="px-4 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 font-medium"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr><td colSpan="7" className="px-6 py-8 text-center text-sm text-gray-500">No lab results found.</td></tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
