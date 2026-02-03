import { useForm, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Create({ sections }) {
    const { data, setData, post, processing, errors } = useForm({
        section_id: '',
        label: '',
        current_price: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/tests');
    };

    return (
        <AppLayout title="Add New Lab Test">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <label htmlFor="section_id" className="block text-sm font-medium text-gray-700">
                                Section <span className="text-red-500">*</span>
                            </label>
                            <select
                                id="section_id"
                                value={data.section_id}
                                onChange={(e) => setData('section_id', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                required
                            >
                                <option value="">Select a section</option>
                                {sections.map((section) => (
                                    <option key={section.section_id} value={section.section_id}>
                                        {section.label}
                                    </option>
                                ))}
                            </select>
                            {errors.section_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.section_id}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="label" className="block text-sm font-medium text-gray-700">
                                Test Name <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="label"
                                value={data.label}
                                onChange={(e) => setData('label', e.target.value)}
                                maxLength="20"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                required
                            />
                            {errors.label && (
                                <p className="mt-1 text-sm text-red-600">{errors.label}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="current_price" className="block text-sm font-medium text-gray-700">
                                Current Price <span className="text-red-500">*</span>
                            </label>
                            <div className="mt-1 relative rounded-md shadow-sm">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span className="text-gray-500 sm:text-sm">₱</span>
                                </div>
                                <input
                                    type="number"
                                    id="current_price"
                                    value={data.current_price}
                                    onChange={(e) => setData('current_price', e.target.value)}
                                    step="0.01"
                                    min="0"
                                    className="block w-full pl-7 pr-12 rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    placeholder="0.00"
                                    required
                                />
                            </div>
                            {errors.current_price && (
                                <p className="mt-1 text-sm text-red-600">{errors.current_price}</p>
                            )}
                        </div>

                        <div className="flex items-center justify-end space-x-4">
                            <Link
                                href="/tests"
                                className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                            >
                                {processing ? 'Saving...' : 'Save Test'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
