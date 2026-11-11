(function (root) {
  'use strict';

  // A complete example CV, used to show what each template looks like when
  // it is filled in. The person is fictional.
  const CONTENT = {
    personal: {
      full_name: 'Ama Mensah',
      headline: 'Marketing Manager',
      email: 'ama.mensah@example.com',
      phone: '+233 24 555 0142',
      location: 'Accra, Ghana',
      website: 'amamensah.com',
      linkedin: 'linkedin.com/in/amamensah',
      photo: '',
    },
    summary: 'Marketing manager with eight years of experience growing consumer brands across West Africa. I plan and run campaigns across digital, retail, and events, turn customer research into clear messages, and lead small teams to deliver on time and on budget.',
    experience: [
      {
        id: 'sample-exp-1', role: 'Marketing Manager', company: 'Kente Foods Ltd', location: 'Accra',
        start_date: '2021-03', end_date: '', current: true,
        bullets: [
          'Grew online sales by 42% in two years with a new social and email programme.',
          'Led a team of five and an annual budget of GH₵ 1.2 million.',
          'Launched three product lines, each reaching break-even within six months.',
        ],
      },
      {
        id: 'sample-exp-2', role: 'Brand Executive', company: 'Volta Beverages', location: 'Tema',
        start_date: '2018-01', end_date: '2021-02', current: false,
        bullets: [
          'Managed national radio and outdoor campaigns reaching four million people.',
          'Cut agency costs by 18% by bringing design and copywriting in-house.',
        ],
      },
      {
        id: 'sample-exp-3', role: 'Marketing Assistant', company: 'Gold Coast Retail', location: 'Accra',
        start_date: '2016-06', end_date: '2017-12', current: false,
        bullets: ['Coordinated in-store promotions across twelve branches.'],
      },
    ],
    education: [
      {
        id: 'sample-edu-1', degree: 'MSc Marketing', school: 'University of Ghana Business School', location: 'Legon',
        start_date: '2019-09', end_date: '2021-07', details: 'Dissertation on mobile-money adoption among small traders.',
      },
      {
        id: 'sample-edu-2', degree: 'BA Communication Studies', school: 'University of Cape Coast', location: 'Cape Coast',
        start_date: '2012-09', end_date: '2016-06', details: '',
      },
    ],
    skills: [
      { id: 'sample-sk-1', name: 'Campaign planning', level: 'Expert' },
      { id: 'sample-sk-2', name: 'Digital marketing', level: 'Advanced' },
      { id: 'sample-sk-3', name: 'Brand strategy', level: 'Advanced' },
      { id: 'sample-sk-4', name: 'Market research', level: 'Advanced' },
      { id: 'sample-sk-5', name: 'Google Analytics', level: 'Proficient' },
      { id: 'sample-sk-6', name: 'Team leadership', level: 'Advanced' },
      { id: 'sample-sk-7', name: 'Budget management', level: 'Proficient' },
      { id: 'sample-sk-8', name: 'Copywriting', level: 'Proficient' },
    ],
    projects: [
      {
        id: 'sample-pr-1', name: 'Taste of Home campaign', role: 'Campaign lead', url: '',
        start_date: '2023-02', end_date: '2023-08',
        description: 'A national campaign for a new snack range: TV, radio, and market activations that reached 2.3 million people.',
      },
    ],
    certifications: [
      { id: 'sample-ce-1', name: 'Google Digital Marketing Certificate', issuer: 'Google', date: '2022-05', url: '' },
      { id: 'sample-ce-2', name: 'Chartered Marketer', issuer: 'Chartered Institute of Marketing Ghana', date: '2020-11', url: '' },
    ],
    languages: [
      { id: 'sample-la-1', name: 'English', level: 'Fluent' },
      { id: 'sample-la-2', name: 'Twi', level: 'Native' },
      { id: 'sample-la-3', name: 'French', level: 'Conversational' },
    ],
    references: [
      { id: 'sample-re-1', name: 'Kwame Owusu', position: 'Head of Marketing', company: 'Kente Foods Ltd', email: 'k.owusu@example.com', phone: '' },
    ],
    interests: ['Community volunteering', 'Photography', 'Running'],
    settings: { density: 'comfortable' },
  };

  /**
   * The example CV in one template, in that template's own colour, layout,
   * and section order.
   */
  function sampleResume(templateKey, accentColor) {
    const api = root.LunettiResume;
    const design = (api && api.TEMPLATES[templateKey]) || { layout: 'stacked', order: 'standard' };
    const content = JSON.parse(JSON.stringify(CONTENT));
    content.settings.layout = design.layout;
    content.settings.section_order = design.order;
    return {
      name: 'Example CV',
      template_key: templateKey,
      language: 'en',
      accent_color: /^#[0-9a-f]{6}$/i.test(accentColor || '') ? accentColor : '#2563eb',
      font_family: api ? api.DEFAULT_FONT : 'Times New Roman',
      content,
    };
  }

  root.LunettiSample = { sampleResume };
})(typeof globalThis !== 'undefined' ? globalThis : this);
