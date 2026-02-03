import { useForm, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Edit({ section }) {
    const { data, setData, put, processing } = useForm({ label: section.label });
    return (
        <AppLayout title="Edit Section">
            <div className="bg-white shadow-sm sm:rounded-lg p-6">
                <form onSubmit={(e) => { e.preventDefault(); put(`/sections/${section.section_id}`); }} className="space-y-4 max-w-2xl">
                    <div><label className="block text-sm font-medium">Section Name *</label><input type="text" value={data.label} onChange={(e) => setData('label', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" required /></div>
                    <div className="flex justify-end space-x-4">
                        <Link href="/sections" className="px-4 py-2 bg-gray-300 rounded-md text-xs uppercase">Cancel</Link>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded-md text-xs uppercase">Update</button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
