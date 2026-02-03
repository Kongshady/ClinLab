import { useForm, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { useState } from 'react';

export default function Tests({ tests, sections, flash }) {
    const [flashMessage, setFlashMessage] = useState(flash?.success);
    const [searchTerm, setSearchTerm] = useState('');
    
    const { data, setData, post, processing, errors, reset } = useForm({
        section_id: '',
        label: '',
        current_price: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/tests', {
            onSuccess: () => {
                reset();
                setFlashMessage('Test added successfully.');
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this test?')) {
            router.delete(`/tests/${id}`, {
                onSuccess: () => setFlashMessage('Test deleted successfully.')
            });
        }
    };

    const filteredTests = tests.data.filter(t => 
        t.label.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (t.section?.label && t.section.label.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    return (
        <AppLayout title="Laboratory Tests Management">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-8">
                    {flashMessage && (
                        <div className="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                            {flashMessage}
                        </div>
                    )}

                    {/* Add New Test Form */}
                    <div className="mb-8">
                        <h2 className="text-xl font-semibold text-gray-800 mb-4">Add New Laboratory Test</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Test Name *</label>
                                    <input
                                        type="text"
                                        value={data.label}
                                        onChange={(e) => setData('label', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Section *</label>
                                    <select
                                        value={data.section_id}
                                        onChange={(e) => setData('section_id', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="">Select Section</option>
                                        {sections && sections.map((section) => (
                                            <option key={section.section_id} value={section.section_id}>
                                                {section.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Current Price *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={data.current_price}
                                        onChange={(e) => setData('current_price', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-6 py-2 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg hover:from-pink-600 hover:to-pink-700 font-medium disabled:opacity-50"
                            >
                                Add Test
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
                            placeholder="Search by test name or section..."
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                        />
                    </div>

                    {/* Tests List */}
                    <div>
                        <h3 className="text-lg font-semibold text-gray-800 mb-4">Tests List</h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Test Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Current Price</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {filteredTests.length > 0 ? filteredTests.map((test) => (
                                        <tr key={test.test_id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 text-sm text-gray-900">{test.label}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{test.section?.label || 'N/A'}</td>
                                            <td className="px-6 py-4 text-sm text-gray-900">₱{parseFloat(test.current_price).toFixed(2)}</td>
                                            <td className="px-6 py-4 text-sm space-x-2">
                                                <button
                                                    onClick={() => router.get(`/tests/${test.test_id}/edit`)}
                                                    className="px-4 py-1.5 bg-orange-500 text-white rounded hover:bg-orange-600 font-medium"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(test.test_id)}
                                                    className="px-4 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 font-medium"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr>
                                            <td colSpan="4" className="px-6 py-4 text-sm text-gray-500 text-center">
                                                No tests found.
                                            </td>
                                        </tr>
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
