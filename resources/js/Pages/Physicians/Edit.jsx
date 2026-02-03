import { useForm, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Edit({ physician }) {
    const { data, setData, put, processing, errors } = useForm({
        physician_name: physician.physician_name || '',
        specialization: physician.specialization || '',
        contact_number: physician.contact_number || '',
        email: physician.email || '',
    });

    return (
        <AppLayout title="Edit Physician">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-8">
                    <h2 className="text-2xl font-semibold text-gray-800 mb-6">Edit Physician Information</h2>
                    <form onSubmit={(e) => { e.preventDefault(); put(`/physicians/${physician.physician_id}`); }} className="space-y-6 max-w-3xl">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Physician Name <span className="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    value={data.physician_name} 
                                    onChange={(e) => setData('physician_name', e.target.value)} 
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" 
                                    required 
                                />
                                {errors.physician_name && <p className="mt-1 text-sm text-red-600">{errors.physician_name}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                                <input 
                                    type="text" 
                                    value={data.specialization} 
                                    onChange={(e) => setData('specialization', e.target.value)} 
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" 
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                                <input 
                                    type="text" 
                                    value={data.contact_number} 
                                    onChange={(e) => setData('contact_number', e.target.value)} 
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" 
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input 
                                    type="email" 
                                    value={data.email} 
                                    onChange={(e) => setData('email', e.target.value)} 
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" 
                                />
                            </div>
                        </div>
                        <div className="flex items-center justify-end space-x-4 pt-4">
                            <Link href="/physicians" className="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium">Cancel</Link>
                            <button type="submit" disabled={processing} className="px-6 py-2 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg hover:from-pink-600 hover:to-pink-700 font-medium disabled:opacity-50">
                                {processing ? 'Updating...' : 'Update Physician'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
