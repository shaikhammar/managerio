import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import PortalLayout from '@/layouts/portal-layout';
import type { Business, Project } from '@/types';

type Props = {
    project: Project;
    business: Business;
};

const statusColors: Record<string, string> = {
    new: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-yellow-100 text-yellow-800',
    review: 'bg-purple-100 text-purple-700',
    completed: 'bg-green-100 text-green-700',
    delivered: 'bg-teal-100 text-teal-700',
    invoiced: 'bg-indigo-100 text-indigo-700',
    closed: 'bg-gray-100 text-gray-600',
};

const statusLabels: Record<string, string> = {
    new: 'New',
    in_progress: 'In Progress',
    review: 'In Review',
    completed: 'Completed',
    delivered: 'Delivered',
    invoiced: 'Invoiced',
    closed: 'Closed',
};

export default function ProjectStatus({ project, business }: Props) {
    return (
        <PortalLayout businessName={business.name} logoPath={business.logo_path}>
            <Head title={`Project Status — ${project.name}`} />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold">{project.name}</h1>
                    {project.reference && (
                        <p className="text-gray-500 text-sm mt-1">Ref: {project.reference}</p>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Project Status</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center gap-3">
                            <span className="text-sm text-gray-500 w-28">Status</span>
                            <span
                                className={`px-3 py-1 rounded-full text-sm font-medium ${statusColors[project.status] ?? 'bg-gray-100 text-gray-600'}`}
                            >
                                {statusLabels[project.status] ?? project.status}
                            </span>
                        </div>

                        {project.deadline && (
                            <div className="flex items-center gap-3">
                                <span className="text-sm text-gray-500 w-28">Deadline</span>
                                <span className="text-sm">{project.deadline}</span>
                            </div>
                        )}

                        {project.source_language && (
                            <div className="flex items-center gap-3">
                                <span className="text-sm text-gray-500 w-28">Source</span>
                                <span className="text-sm">{project.source_language.name}</span>
                            </div>
                        )}

                        {project.targets && project.targets.length > 0 && (
                            <div className="flex items-start gap-3">
                                <span className="text-sm text-gray-500 w-28 pt-0.5">Targets</span>
                                <div className="flex flex-wrap gap-2">
                                    {project.targets.map((target) => (
                                        <span
                                            key={target.id}
                                            className="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-sm"
                                        >
                                            {target.language_pair?.target_language?.name ?? `Target ${target.id}`}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        )}

                        {project.service_type && (
                            <div className="flex items-center gap-3">
                                <span className="text-sm text-gray-500 w-28">Service</span>
                                <span className="text-sm">{project.service_type.name}</span>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </PortalLayout>
    );
}
