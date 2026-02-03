import { useForm, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        physician_name: '',
        specialization: '',
        contact_number: '',
        email: '',
    });

    return (
        <AppLayout title="Add New Physician">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-6">
                    <form onSubmit={(e) => { e.preventDefault(); post('/physicians'); }} className="space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Physician Name <span className="text-red-500">*</span></label>
                            <input type="text" value={data.physician_name} onChange={(e) => setData('physician_name', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required />
                            {errors.physician_name && <p className="mt-1 text-sm text-red-600">{errors.physician_name}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Specialization</label>
                            <input type="text" value={data.specialization} onChange={(e) => setData('specialization', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Contact Number</label>
                            <input type="text" value={data.contact_number} onChange={(e) => setData('contact_number', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        </div>
                        <div className="flex items-center justify-end space-x-4">
                            <Link href="/physicians" className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">Cancel</Link>
                            <button type="submit" disabled={processing} className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50">
                                {processing ? 'Saving...' : 'Save Physician'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
