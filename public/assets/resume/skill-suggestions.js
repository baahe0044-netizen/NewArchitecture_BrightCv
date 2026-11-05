(function (root) {
  'use strict';

  // Skill suggestions for the builder's Skills step, worked out on the device
  // from the CV's own headline and job titles. Nothing is sent anywhere.
  //
  // Each group lists the words that point to a kind of work (matched against
  // the titles) and the skills people in that work commonly list.
  const GROUPS = [
    {
      match: ['software', 'developer', 'programmer', 'engineer', 'web', 'frontend', 'front-end', 'backend', 'back-end', 'full-stack', 'fullstack', 'devops', 'it', 'mobile', 'app'],
      skills: ['JavaScript', 'PHP', 'Python', 'SQL', 'Git', 'HTML', 'CSS', 'React', 'Node.js', 'REST APIs', 'Testing', 'Problem solving'],
    },
    {
      match: ['data', 'analyst', 'analytics', 'statistic', 'scientist', 'bi', 'business intelligence'],
      skills: ['Excel', 'SQL', 'Power BI', 'Python', 'Data analysis', 'Data visualisation', 'Statistics', 'Reporting', 'Tableau', 'Attention to detail'],
    },
    {
      match: ['design', 'designer', 'ux', 'ui', 'graphic', 'creative', 'artist', 'illustrat'],
      skills: ['Figma', 'Adobe Photoshop', 'Adobe Illustrator', 'UI design', 'UX research', 'Prototyping', 'Typography', 'Branding', 'Canva', 'Creativity'],
    },
    {
      match: ['marketing', 'brand', 'social media', 'content', 'seo', 'digital', 'communications', 'pr', 'public relations'],
      skills: ['Digital marketing', 'Social media management', 'SEO', 'Content writing', 'Google Analytics', 'Email marketing', 'Campaign planning', 'Copywriting', 'Market research', 'Canva'],
    },
    {
      match: ['sales', 'business development', 'account manager', 'account executive', 'relationship'],
      skills: ['Negotiation', 'Customer relationship management', 'Lead generation', 'CRM software', 'Sales forecasting', 'Presentation skills', 'Cold calling', 'Closing deals', 'Communication'],
    },
    {
      match: ['finance', 'financial', 'accountant', 'accounting', 'audit', 'bank', 'treasury', 'tax', 'bookkeep'],
      skills: ['Financial reporting', 'Budgeting', 'Excel', 'QuickBooks', 'Reconciliation', 'Financial analysis', 'Tax compliance', 'Auditing', 'IFRS', 'Attention to detail'],
    },
    {
      match: ['hr', 'human resource', 'recruit', 'talent', 'people', 'payroll'],
      skills: ['Recruitment', 'Onboarding', 'Employee relations', 'Payroll', 'HR policies', 'Performance management', 'Interviewing', 'Labour law', 'Confidentiality'],
    },
    {
      match: ['teacher', 'teaching', 'tutor', 'lecturer', 'educat', 'instructor', 'school'],
      skills: ['Lesson planning', 'Classroom management', 'Curriculum development', 'Student assessment', 'Public speaking', 'Mentoring', 'Patience', 'Microsoft Office'],
    },
    {
      match: ['nurse', 'nursing', 'doctor', 'medical', 'health', 'clinic', 'hospital', 'pharmac', 'midwife', 'caregiver', 'care'],
      skills: ['Patient care', 'CPR', 'First aid', 'Medication administration', 'Infection control', 'Patient records', 'Triage', 'Compassion', 'Teamwork'],
    },
    {
      match: ['customer', 'support', 'service', 'call centre', 'call center', 'help desk', 'helpdesk', 'reception'],
      skills: ['Customer service', 'Communication', 'Complaint handling', 'Problem solving', 'CRM software', 'Active listening', 'Patience', 'Multitasking'],
    },
    {
      match: ['admin', 'assistant', 'secretary', 'office', 'clerk', 'coordinator', 'receptionist'],
      skills: ['Microsoft Office', 'Scheduling', 'Data entry', 'Record keeping', 'Email management', 'Organisation', 'Time management', 'Communication'],
    },
    {
      match: ['project', 'programme', 'program manager', 'scrum', 'product manager', 'operations'],
      skills: ['Project management', 'Stakeholder management', 'Risk management', 'Agile', 'Scrum', 'Budgeting', 'Planning', 'Leadership', 'Reporting'],
    },
    {
      match: ['civil', 'mechanical', 'electrical', 'technician', 'construction', 'maintenance', 'site', 'architect'],
      skills: ['AutoCAD', 'Technical drawing', 'Health and safety', 'Project planning', 'Quality control', 'Troubleshooting', 'Site supervision', 'Problem solving'],
    },
    {
      match: ['hotel', 'hospitality', 'chef', 'cook', 'restaurant', 'waiter', 'waitress', 'catering', 'barista', 'tourism'],
      skills: ['Customer service', 'Food safety', 'Food preparation', 'Teamwork', 'Cash handling', 'Time management', 'Menu planning', 'Hygiene standards'],
    },
    {
      match: ['logistics', 'supply chain', 'procurement', 'warehouse', 'driver', 'transport', 'inventory', 'store'],
      skills: ['Inventory management', 'Supply chain management', 'Procurement', 'Logistics planning', 'Vendor management', 'Excel', 'Forklift operation', 'Attention to detail'],
    },
    {
      match: ['writer', 'journalist', 'editor', 'author', 'copywriter', 'translator'],
      skills: ['Writing', 'Editing', 'Proofreading', 'Research', 'Storytelling', 'Content strategy', 'SEO', 'Interviewing'],
    },
    {
      match: ['law', 'legal', 'lawyer', 'paralegal', 'attorney', 'solicitor', 'compliance'],
      skills: ['Legal research', 'Contract drafting', 'Compliance', 'Case management', 'Negotiation', 'Attention to detail', 'Legal writing'],
    },
  ];

  // Offered when the titles do not point to any one kind of work.
  const GENERAL = [
    'Communication', 'Teamwork', 'Problem solving', 'Time management', 'Microsoft Office',
    'Leadership', 'Customer service', 'Organisation', 'Adaptability', 'Critical thinking',
  ];

  /**
   * @param {string[]} titles   The headline and the job titles on the CV.
   * @param {string[]} existing Skill names the CV already has.
   * @param {number}   limit    How many to return.
   */
  function suggest(titles, existing, limit = 12) {
    const text = (titles || []).join(' ').toLowerCase();
    // A word must start where a word starts ("design" matches "designer"),
    // and a short one must also end there, so "ui" never matches "guide".
    const mentions = (word) => {
      const escaped = word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      return new RegExp('(^|[^a-z])' + escaped + (word.length <= 3 ? '($|[^a-z])' : '')).test(text);
    };
    const have = new Set((existing || []).map((name) => String(name || '').trim().toLowerCase()).filter(Boolean));
    const picked = [];
    const add = (skill) => {
      const key = skill.toLowerCase();
      if (!have.has(key) && !picked.some((item) => item.toLowerCase() === key)) picked.push(skill);
    };

    GROUPS
      .filter((group) => group.match.some(mentions))
      .forEach((group) => group.skills.forEach(add));
    GENERAL.forEach(add);

    return picked.slice(0, limit);
  }

  root.LunettiSkills = { suggest };
})(typeof globalThis !== 'undefined' ? globalThis : this);
